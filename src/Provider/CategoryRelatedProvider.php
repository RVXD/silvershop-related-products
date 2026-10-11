<?php

declare(strict_types=1);

namespace SilverShop\RelatedProducts\Provider;

use SilverShop\Page\Product;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;

/**
 * Related products that share a category with the given product (most popular first) — the WooCommerce-style default.
 * Uses the effective-category index from silvershop/category-index when present (recursive, ancestors included),
 * otherwise the product's own primary + secondary categories.
 */
class CategoryRelatedProvider implements RelatedProductsProvider
{
    use Injectable;

    public function getRelatedProducts(Product $product, int $limit): SS_List
    {
        $categoryIDs = $this->categoryIDsFor($product);
        if (!$categoryIDs) {
            return ArrayList::create();
        }

        // Same primary category (ParentID) or listed as a secondary category (ProductCategories). Two plain
        // queries merged, to avoid filterAny() joining across a many_many.
        $ids = array_merge(
            Product::get()->filter('ParentID', $categoryIDs)->column('ID'),
            Product::get()->filter('ProductCategories.ID', $categoryIDs)->column('ID')
        );
        $ids = array_diff(array_unique(array_map('intval', $ids)), [(int) $product->ID]);
        if (!$ids) {
            return ArrayList::create();
        }

        return Product::get()
            ->filter('ID', $ids)
            ->sort('Popularity', 'DESC')
            ->limit($limit);
    }

    /**
     * @return array<int,int>
     */
    private function categoryIDsFor(Product $product): array
    {
        if ($product->hasMethod('EffectiveCategories')) {
            $ids = $product->EffectiveCategories()->column('ID');
            if ($ids) {
                return array_map('intval', $ids);
            }
        }

        $ids = $product->ProductCategories()->column('ID');
        if ($product->ParentID) {
            $ids[] = $product->ParentID;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
