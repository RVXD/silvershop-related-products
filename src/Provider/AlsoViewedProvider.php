<?php

declare(strict_types=1);

namespace SilverShop\RelatedProducts\Provider;

use SilverShop\Analytics\Model\ProductView;
use SilverShop\Page\Product;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;

/**
 * "Customers who viewed this also viewed" — other products seen by visitors who viewed this one, ranked by co-view
 * count. Soft-depends on silvershop/analytics — returns nothing when its ProductView model is not installed.
 */
class AlsoViewedProvider implements RelatedProductsProvider
{
    use Injectable;

    public function getRelatedProducts(Product $product, int $limit): SS_List
    {
        if (!class_exists(ProductView::class)) {
            return ArrayList::create();
        }

        $visitors = array_filter(ProductView::get()->filter('ProductID', $product->ID)->columnUnique('VisitorHash'));
        if (!$visitors) {
            return ArrayList::create();
        }

        $productIDs = ProductView::get()
            ->filter('VisitorHash', $visitors)
            ->exclude('ProductID', [0, $product->ID])
            ->column('ProductID');
        $counts = array_count_values(array_filter(array_map('intval', $productIDs)));
        arsort($counts);
        $rankedIDs = array_slice(array_keys($counts), 0, $limit > 0 ? $limit : count($counts));
        if (!$rankedIDs) {
            return ArrayList::create();
        }

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
