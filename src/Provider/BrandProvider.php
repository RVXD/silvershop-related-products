<?php

declare(strict_types=1);

namespace SilverShop\RelatedProducts\Provider;

use SilverShop\Page\Product;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;

/**
 * Other products of the same brand (most popular first). Soft-depends on silvershop/brands — returns nothing when the
 * Brand relation is absent or the product has no brand.
 */
class BrandProvider implements RelatedProductsProvider
{
    use Injectable;

    public function getRelatedProducts(Product $product, int $limit): SS_List
    {
        if (!$product->hasMethod('Brand') || !$product->BrandID) {
            return ArrayList::create();
        }

        return Product::get()
            ->filter('BrandID', $product->BrandID)
            ->exclude('ID', $product->ID)
            ->sort('Popularity', 'DESC')
            ->limit($limit);
    }
}
