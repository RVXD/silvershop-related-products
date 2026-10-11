<?php

declare(strict_types=1);

namespace SilverShop\RelatedProducts\Provider;

use SilverShop\Model\Order;
use SilverShop\Model\Product\OrderItem;
use SilverShop\Page\Product;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;

/**
 * "Frequently bought together" — other products that appear in placed orders alongside the given product, ranked by
 * how often they co-occur. The natural signal for cross-sells (Amazon/Shopify style).
 */
class BoughtTogetherProvider implements RelatedProductsProvider
{
    use Injectable;

    public function getRelatedProducts(Product $product, int $limit): SS_List
    {
        $placed = (array) Order::config()->get('placed_status');

        $orderIDs = OrderItem::get()
            ->filter(['ProductID' => $product->ID, 'Order.Status' => $placed])
            ->columnUnique('OrderID');
        $orderIDs = array_values(array_filter(array_map('intval', $orderIDs)));
        if (!$orderIDs) {
            return ArrayList::create();
        }

        $productIDs = OrderItem::get()
            ->filter('OrderID', $orderIDs)
            ->exclude('ProductID', [0, $product->ID])
            ->column('ProductID');
        $counts = array_count_values(array_filter(array_map('intval', $productIDs)));
        arsort($counts);
        $rankedIDs = array_slice(array_keys($counts), 0, $limit > 0 ? $limit : count($counts));
        if (!$rankedIDs) {
            return ArrayList::create();
        }

        // Fetch the (still-published) products and return them in co-occurrence rank order.
        $byID = [];
        foreach (Product::get()->filter('ID', $rankedIDs) as $candidate) {
            $byID[(int) $candidate->ID] = $candidate;
        }
        $result = ArrayList::create();
        foreach ($rankedIDs as $id) {
            if (isset($byID[$id])) {
                $result->push($byID[$id]);
            }
        }

        return $result;
    }
}
