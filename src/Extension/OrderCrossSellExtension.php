<?php

declare(strict_types=1);

namespace SilverShop\RelatedProducts\Extension;

use SilverShop\Model\Order;
use SilverShop\Page\Product;
use SilverStripe\Core\Extension;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;

/**
 * Cart-level cross-sells: the union of every cart product's cross-sell links, with products already in the cart
 * removed and a limit applied. Rendered in the cart via the CartCrossSells include.
 *
 * @extends Extension<Order>
 */
class OrderCrossSellExtension extends Extension
{
    public function CrossSells(?int $limit = null): SS_List
    {
        $inCart = [];
        foreach ($this->getOwner()->Items() as $item) {
            if ($item->ProductID) {
                $inCart[(int) $item->ProductID] = true;
            }
        }
        if (!$inCart) {
            return ArrayList::create();
        }

        $crossSell = [];
        foreach (Product::get()->filter('ID', array_keys($inCart)) as $product) {
            foreach ($product->CrossSellProducts()->column('ID') as $id) {
                $crossSell[(int) $id] = true;
            }
        }

        $ids = array_diff(array_keys($crossSell), array_keys($inCart));
        if (!$ids) {
            return ArrayList::create();
        }

        $list = Product::get()->filter('ID', $ids);
        $limit ??= (int) Product::config()->get('related_products_limit');

        return $limit > 0 ? $list->limit($limit) : $list;
    }
}
