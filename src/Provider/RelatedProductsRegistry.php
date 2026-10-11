<?php

declare(strict_types=1);

namespace SilverShop\RelatedProducts\Provider;

use SilverShop\Page\Product;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;

/**
 * Runs the configured {@link RelatedProductsProvider}s in order, merging their results (de-duplicated, product
 * itself excluded) up to a limit. Used by the display accessors to auto-fill an empty relation slot.
 */
class RelatedProductsRegistry
{
    use Injectable;
    use Configurable;

    /**
     * Ordered provider class names. First match wins for a given product; later providers top up to the limit.
     *
     * @var array<int,class-string<RelatedProductsProvider>>
     */
    private static array $providers = [];

    public function getRelatedProducts(Product $product, int $limit): SS_List
    {
        $limit = $limit > 0 ? $limit : PHP_INT_MAX;
        $seen = [(int) $product->ID => true];
        $result = ArrayList::create();

        foreach ((array) $this->config()->get('providers') as $class) {
            if (!is_a($class, RelatedProductsProvider::class, true)) {
                continue;
            }
            /** @var RelatedProductsProvider $provider */
            $provider = Injector::inst()->create($class);
            foreach ($provider->getRelatedProducts($product, $limit) as $candidate) {
                $id = (int) $candidate->ID;
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $result->push($candidate);
                if ($result->count() >= $limit) {
                    return $result;
                }
            }
        }

        return $result;
    }
}
