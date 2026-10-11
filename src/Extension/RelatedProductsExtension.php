<?php

declare(strict_types=1);

namespace SilverShop\RelatedProducts\Extension;

use SilverShop\Page\Product;
use SilverShop\RelatedProducts\Provider\RelatedProductsRegistry;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Model\List\SS_List;

/**
 * Related / up-sell / cross-sell product relations on a {@link Product}.
 *
 * Three one-directional many_many self-relations, curated in a "Related" CMS tab (add existing products by search).
 * The storefront display accessors ({@link RelatedItems()}, {@link UpSells()}, {@link CrossSells()}) return the
 * curated products with the current product excluded and a configurable limit applied; unpublished products fall
 * away automatically on the live stage. Cross-sells are also aggregated across the whole cart by
 * {@link OrderCrossSellExtension}.
 *
 * @extends Extension<Product>
 */
class RelatedProductsExtension extends Extension
{
    /** Default number of products returned by each display accessor (0 = no limit). */
    private static int $related_products_limit = 4;

    /** Relation slots that fall back to the automatic providers when they have no manual picks. */
    private static array $auto_fill_slots = ['RelatedProducts', 'CrossSellProducts'];

    private static array $many_many = [
        'RelatedProducts' => Product::class,
        'UpSellProducts' => Product::class,
        'CrossSellProducts' => Product::class,
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->addFieldToTab('Root.Related', LiteralField::create(
            'RelatedProductsIntro',
            '<p class="message notice" style="display:flex;align-items:flex-start;gap:.5em">'
            . '<span class="font-icon-info-circled" aria-hidden="true"></span><span>' . _t(
                self::class . '.Intro',
                'Link other products to this one. <strong>Up-sells</strong> (better or alternative versions) and '
                . '<strong>related</strong> products show on this product\'s page; <strong>cross-sells</strong> '
                . '(things bought together) show in the cart. Use the search box to add existing products.'
            ) . '</span></p>'
        ));

        $slots = [
            'UpSellProducts' => _t(self::class . '.UpSellProducts', 'Up-sell products'),
            'CrossSellProducts' => _t(self::class . '.CrossSellProducts', 'Cross-sell products'),
            'RelatedProducts' => _t(self::class . '.RelatedProducts', 'Related products'),
        ];
        foreach ($slots as $name => $title) {
            $config = GridFieldConfig_RelationEditor::create();
            // Curate existing products only — no "add new product" button here.
            $config->removeComponentsByType(GridFieldAddNewButton::class);
            $columns = $config->getComponentByType(GridFieldDataColumns::class);
            if ($columns instanceof GridFieldDataColumns) {
                $columns->setDisplayFields([
                    'InternalItemID' => _t(self::class . '.ColCode', 'Code'),
                    'Title' => _t(self::class . '.ColTitle', 'Title'),
                    'Price.Nice' => _t(self::class . '.ColPrice', 'Price'),
                ]);
            }

            $fields->addFieldToTab(
                'Root.Related',
                GridField::create($name, $title, $this->getOwner()->$name(), $config)
            );
        }
    }

    public function RelatedItems(?int $limit = null): SS_List
    {
        return $this->relatedSlot('RelatedProducts', $limit);
    }

    public function UpSells(?int $limit = null): SS_List
    {
        return $this->relatedSlot('UpSellProducts', $limit);
    }

    public function CrossSells(?int $limit = null): SS_List
    {
        return $this->relatedSlot('CrossSellProducts', $limit);
    }

    private function relatedSlot(string $relation, ?int $limit): SS_List
    {
        $owner = $this->getOwner();
        $limit ??= (int) $owner->config()->get('related_products_limit');
        $curated = $owner->$relation()->exclude('ID', $owner->ID);

        if ($curated->exists()) {
            return $limit > 0 ? $curated->limit($limit) : $curated;
        }

        // Empty slot: fall back to the automatic providers when this slot opts in.
        if (in_array($relation, (array) $owner->config()->get('auto_fill_slots'), true)) {
            return RelatedProductsRegistry::singleton()->getRelatedProducts($owner, $limit);
        }

        return $curated;
    }
}
