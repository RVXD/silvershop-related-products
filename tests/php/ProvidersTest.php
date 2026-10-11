<?php

namespace SilverShop\RelatedProducts\Tests;

use SilverShop\Model\Order;
use SilverShop\Model\Product\OrderItem;
use SilverShop\Page\Product;
use SilverShop\Page\ProductCategory;
use SilverShop\RelatedProducts\Provider\BoughtTogetherProvider;
use SilverShop\RelatedProducts\Provider\CategoryRelatedProvider;
use SilverStripe\Dev\SapphireTest;

class ProvidersTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static bool $use_draft_site = true;

    /**
     * @return array{0: ProductCategory, 1: Product, 2: Product}
     */
    private function categoryWithProducts(): array
    {
        $category = ProductCategory::create(['Title' => 'Cat']);
        $category->write();
        $a = Product::create(['Title' => 'A', 'BasePrice' => 10, 'ParentID' => $category->ID]);
        $a->write();
        $b = Product::create(['Title' => 'B', 'BasePrice' => 10, 'ParentID' => $category->ID]);
        $b->write();

        return [$category, $a, $b];
    }

    public function testCategoryProviderReturnsSameCategoryProductsExcludingSelf(): void
    {
        [, $a, $b] = $this->categoryWithProducts();

        $ids = (new CategoryRelatedProvider())->getRelatedProducts($a, 10)->column('ID');

        $this->assertContains($b->ID, $ids);
        $this->assertNotContains($a->ID, $ids);
    }

    public function testBoughtTogetherProviderReturnsCoPurchasedProducts(): void
    {
        $p = Product::create(['Title' => 'P', 'BasePrice' => 10]);
        $p->write();
        $q = Product::create(['Title' => 'Q', 'BasePrice' => 10]);
        $q->write();

        $placed = (array) Order::config()->get('placed_status');
        $order = Order::create(['Status' => $placed[0]]);
        $order->write();
        OrderItem::create(['OrderID' => $order->ID, 'ProductID' => $p->ID, 'Quantity' => 1])->write();
        OrderItem::create(['OrderID' => $order->ID, 'ProductID' => $q->ID, 'Quantity' => 1])->write();

        $this->assertSame([$q->ID], (new BoughtTogetherProvider())->getRelatedProducts($p, 10)->column('ID'));
    }

    public function testEmptyRelatedSlotAutoFillsAndCuratedWins(): void
    {
        [, $a, $b] = $this->categoryWithProducts();

        // No manual related picks → auto-fills from the providers (same category → B).
        $this->assertContains($b->ID, $a->RelatedItems()->column('ID'), 'empty related slot auto-fills');

        // A manual pick takes over — no auto-fill once curated.
        $c = Product::create(['Title' => 'C', 'BasePrice' => 10]);
        $c->write();
        $a->RelatedProducts()->add($c);
        $this->assertSame([$c->ID], $a->RelatedItems()->column('ID'), 'curated related wins over auto');
    }

    public function testUpSellsDoNotAutoFill(): void
    {
        [, $a] = $this->categoryWithProducts();

        $this->assertCount(0, $a->UpSells(), 'up-sells are manual only (not in auto_fill_slots)');
    }
}
