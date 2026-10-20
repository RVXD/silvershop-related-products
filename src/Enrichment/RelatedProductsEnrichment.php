<?php

declare(strict_types=1);

namespace SilverShop\RelatedProducts\Enrichment;

use SilverShop\Api\Enrichment\ProductEnrichmentExtension;
use SilverShop\Page\Product;

/**
 * Adds `upSells`, `relatedItems` and `crossSells` product-id arrays to the serialised product when
 * silvershop-suite/related-products is installed.
 *
 * These ride the extension's storefront display accessors ({@see \SilverShop\RelatedProducts\Extension\
 * RelatedProductsExtension::UpSells()} / RelatedItems() / CrossSells()), NOT the raw many_many relations, so the api
 * returns exactly what the product page / cart would show: the current product excluded, the configured limit
 * applied, and the automatic-provider fallback used for the empty related / cross-sell slots. Ids only (not nested
 * products) — a client fetches the referenced products through the normal catalog resolver.
 *
 * Registered onto {@see \SilverShop\Api\Serializer\ProductSerializer} only when silvershop-suite/api is installed
 * (see _config/api.yml).
 */
class RelatedProductsEnrichment extends ProductEnrichmentExtension
{
    /**
     * @param array<string, mixed> $data
     */
    public function updateSerialisedProduct(array &$data, Product $product): void
    {
        $data['upSells'] = $this->ids($this->accessor($product, 'UpSells'));
        $data['relatedItems'] = $this->ids($this->accessor($product, 'RelatedItems'));
        $data['crossSells'] = $this->ids($this->accessor($product, 'CrossSells'));
    }

    /**
     * The ids of an iterable of products (empty when the list is absent or empty).
     *
     * @return array<int>
     */
    private function ids(mixed $list): array
    {
        if (!is_iterable($list)) {
            return [];
        }

        $ids = [];
        foreach ($list as $related) {
            if ($related && $related->exists()) {
                $ids[] = (int) $related->ID;
            }
        }

        return $ids;
    }
}
