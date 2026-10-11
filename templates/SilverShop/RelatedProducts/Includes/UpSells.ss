<%-- Up-sell products for the current product. Include on the product page: <% include SilverShop/RelatedProducts/UpSells %>.
     Minimal markup — the theme styles .shop-related / .product. --%>
<% if $UpSells %>
<div class="shop-related shop-upsells">
    <h2><%t SilverShop\RelatedProducts\Extension\RelatedProductsExtension.UpSellsHeading 'You may also like' %></h2>
    <ul class="products">
        <% loop $UpSells %>
            <li class="product">
                <a href="$Link">
                    <% if $Image %><img src="$Image.ScaleWidth(300).URL" alt="$Title.ATT" loading="lazy"><% end_if %>
                    <span class="title">$Title</span>
                    <span class="price">$Price.Nice</span>
                </a>
            </li>
        <% end_loop %>
    </ul>
</div>
<% end_if %>
