# SilverShop Related Products

Related, up-sell and cross-sell product relations for [SilverShop](https://github.com/silvershop/silvershop-core)
(Silverstripe CMS 6). Hand-curate which products are linked to each other and show them on the product page and in
the cart — the classic merchandising lift SilverShop core doesn't ship.

## Requirements

- Silverstripe CMS `^6.0`, SilverShop core `^6`

## Installation

```bash
composer require silvershop-suite/related-products
```

Run `dev/build?flush=1`.

## What you get

Three one-directional relations are added to every product, curated under a **Related** tab in the product editor
(add existing products with the search box):

| Relation | Meaning | Shown |
|----------|---------|-------|
| **Up-sell** | a better / alternative version of this product | product page |
| **Related** | similar or complementary products | product page |
| **Cross-sell** | products bought together | cart |

Relations are one-directional (linking A→B does not link B→A), matching Magento/WooCommerce.

## Showing them in a theme

The module ships minimal includes (your theme owns the styling of `.shop-related` / `.product`).

On the **product page** (a `Product` is in scope):

```ss
<% include SilverShop/RelatedProducts/UpSells %>
<% include SilverShop/RelatedProducts/RelatedItems %>
```

In the **cart** (an `Order` is in scope):

```ss
<% include SilverShop/RelatedProducts/CartCrossSells %>
```

Or use the accessors directly: `$UpSells`, `$RelatedItems`, `$CrossSells` on a product, and `$CrossSells` on the cart
(aggregated across all cart products, excluding those already in the cart). Each is filtered (current product
excluded, unpublished products dropped) and limited.

## Configuration

```yaml
SilverShop\Page\Product:
  related_products_limit: 4   # max products per block (0 = no limit)
```

The accessors also take an explicit limit: `$UpSells(8)`.

## Automatic fallback

When a slot has no manual picks, it falls back to pluggable providers. Out of the box **Related** and **Cross-sell**
auto-fill (up-sells stay manual). Providers run in order and de-duplicate:

1. **Bought together** — products co-purchased in placed orders.
2. **Also viewed** — products viewed by the same visitors (soft-dep `silvershop-suite/analytics`).
3. **Same brand** — other products of the same brand (soft-dep `silvershop-suite/brands`).
4. **Same category** — products sharing a category (uses `silvershop-suite/category-index` when installed, else the
   product's own primary/secondary categories).

Soft-dep providers simply return nothing when their module is absent. A manual pick always wins — curate a slot and
the fallback steps aside.

```yaml
SilverShop\Page\Product:
  auto_fill_slots:            # slots that auto-fill when empty (default: Related + Cross-sell)
    - RelatedProducts
    - CrossSellProducts

SilverShop\RelatedProducts\Provider\RelatedProductsRegistry:
  providers:                 # ordered; de-duplicated across providers
    - SilverShop\RelatedProducts\Provider\BoughtTogetherProvider
    - SilverShop\RelatedProducts\Provider\AlsoViewedProvider
    - SilverShop\RelatedProducts\Provider\BrandProvider
    - SilverShop\RelatedProducts\Provider\CategoryRelatedProvider
```

## License

BSD-3-Clause. See [LICENSE](LICENSE).

## Credits

Built and maintained by **Remy Vaartjes** (<remy@xd.nl>) — part of the **SilverShop-Suite**.
