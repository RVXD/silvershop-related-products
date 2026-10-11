<?php

namespace SilverShop\RelatedProducts\Tests;

use SilverShop\Model\Order;
use SilverShop\Model\Product\OrderItem;
use SilverShop\Page\Product;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

class RelatedProductsTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static bool $use_draft_site = true;

    private function product(string $title): Product
    {
        $product = Product::create(['Title' => $title, 'BasePrice' => 10]);
        $product->write();

        return $product;
    }

    public function testCurationPersistsAcrossTheThreeRelations(): void
    {
        $a = $this->product('A');
        $b = $this->product('B');
        $c = $this->product('C');

        $a->UpSellProducts()->add($b);
        $a->RelatedProducts()->add($c);
        $a->CrossSellProducts()->add($b);

        $this->assertSame([$b->ID], $a->UpSellProducts()->column('ID'));
        $this->assertSame([$c->ID], $a->RelatedProducts()->column('ID'));
        $this->assertSame([$b->ID], $a->CrossSellProducts()->column('ID'));
    }

    public function testDisplayAccessorsExcludeSelfAndRespectLimit(): void
    {
        $a = $this->product('A');
        $b = $this->product('B');
        $c = $this->product('C');

        // Self-link should never be shown.
        $a->UpSellProducts()->add($a);
        $a->UpSellProducts()->add($b);
        $a->UpSellProducts()->add($c);

        $ids = $a->UpSells()->column('ID');
        $this->assertNotContains($a->ID, $ids, 'the product itself is excluded');
        $this->assertEqualsCanonicalizing([$b->ID, $c->ID], $ids);

        Config::modify()->set(Product::class, 'related_products_limit', 1);
        $this->assertCount(1, $a->UpSells(), 'limit config applied');
        $this->assertCount(2, $a->UpSells(0), 'explicit 0 limit returns all (self still excluded)');
    }

    public function testOrderCrossSellsAggregateAndExcludeCartProducts(): void
    {
        $p1 = $this->product('P1');
        $p2 = $this->product('P2');
        $x = $this->product('X');
        $y = $this->product('Y');

        $p1->CrossSellProducts()->add($x);
        $p1->CrossSellProducts()->add($p2); // already in the cart → excluded
        $p2->CrossSellProducts()->add($y);

        $order = Order::create();
        $order->write();
        foreach ([$p1, $p2] as $product) {
            OrderItem::create(['OrderID' => $order->ID, 'ProductID' => $product->ID, 'Quantity' => 1])->write();
        }

        $this->assertEqualsCanonicalizing(
            [$x->ID, $y->ID],
            $order->CrossSells()->column('ID'),
            'cart cross-sells are the union of each product\'s cross-sells, minus products already in the cart'
        );
    }
}
