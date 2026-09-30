<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        .meta { color: #666; margin-bottom: 20px; }
        table.addresses { width: 100%; margin-bottom: 22px; }
        table.addresses td { vertical-align: top; width: 50%; }
        h3 { font-size: 10px; text-transform: uppercase; color: #888; margin: 0 0 4px; letter-spacing: .5px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { text-align: left; border-bottom: 2px solid #333; padding: 6px 4px; font-size: 10px; text-transform: uppercase; }
        table.items td { border-bottom: 1px solid #ddd; padding: 6px 4px; }
        .qty { text-align: right; width: 60px; }
    </style>
</head>
<body>
    <h1><%t SilverShop\ShippingLabels\PackingSlip.TITLE 'Packing slip' %></h1>
    <div class="meta">$Reference<% if $Placed %> &middot; $Placed.Nice<% end_if %></div>

    <table class="addresses">
        <tr>
            <td>
                <h3><%t SilverShop\ShippingLabels\PackingSlip.FROM 'From' %></h3>
                <% with $SiteConfig %>
                    $LabelSenderName<br>
                    $LabelSenderAddressHTML
                <% end_with %>
            </td>
            <td>
                <h3><%t SilverShop\ShippingLabels\PackingSlip.SHIPTO 'Ship to' %></h3>
                <% with $ShippingAddress %>
                    $Name<br>
                    <% if $Company %>$Company<br><% end_if %>
                    $Address<br>
                    <% if $AddressLine2 %>$AddressLine2<br><% end_if %>
                    $PostalCode $City<br>
                    <% if $State %>$State<br><% end_if %>
                    $Country
                <% end_with %>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th><%t SilverShop\ShippingLabels\PackingSlip.PRODUCT 'Product' %></th>
                <th class="qty"><%t SilverShop\ShippingLabels\PackingSlip.QTY 'Qty' %></th>
            </tr>
        </thead>
        <tbody>
            <% loop $Items %>
                <tr>
                    <td>$TableTitle</td>
                    <td class="qty">$Quantity</td>
                </tr>
            <% end_loop %>
        </tbody>
    </table>
</body>
</html>
