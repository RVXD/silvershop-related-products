<%-- Cross-sell products for the cart. Include where the cart (an Order) is in scope:
     <% include SilverShop/RelatedProducts/CartCrossSells %>. --%>
<% if $CrossSells %>
<div class="shop-related shop-crosssells">
    <h2><%t SilverShop\RelatedProducts\Extension\OrderCrossSellExtension.CrossSellsHeading 'Frequently bought together' %></h2>
    <ul class="products">
        <% loop $CrossSells %>
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
