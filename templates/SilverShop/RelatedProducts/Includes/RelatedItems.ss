<%-- Related products for the current product. Include on the product page: <% include SilverShop/RelatedProducts/RelatedItems %>. --%>
<% if $RelatedItems %>
<div class="shop-related shop-related-items">
    <h2><%t SilverShop\RelatedProducts\Extension\RelatedProductsExtension.RelatedHeading 'Related products' %></h2>
    <ul class="products">
        <% loop $RelatedItems %>
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
