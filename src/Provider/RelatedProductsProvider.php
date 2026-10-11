<?php

declare(strict_types=1);

namespace SilverShop\RelatedProducts\Provider;

use SilverShop\Page\Product;
use SilverStripe\Model\List\SS_List;

/**
 * Supplies automatic related-product candidates for a product, used to fill a relation slot that has no manual
 * picks. Registered (and ordered) in {@link RelatedProductsRegistry}.
 */
interface RelatedProductsProvider
{
    /**
     * Candidate products related to $product, best first, at most $limit. The caller excludes the product itself
     * and de-duplicates across providers.
     */
    public function getRelatedProducts(Product $product, int $limit): SS_List;
}
