<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { font-family: "DejaVu Sans", sans-serif; color: #000; margin: 0; padding: 12px; font-size: 11px; }
        .carrier { font-size: 20px; font-weight: bold; text-transform: uppercase; }
        .service { font-size: 11px; color: #333; margin-bottom: 8px; }
        .box { border: 1px solid #000; padding: 8px; margin-bottom: 8px; }
        h3 { font-size: 8px; text-transform: uppercase; letter-spacing: .5px; margin: 0 0 3px; color: #444; }
        .to { font-size: 15px; line-height: 1.35; }
        .to .name { font-weight: bold; }
        .meta { font-size: 9px; color: #333; margin-bottom: 8px; }
        .tracking { border-top: 2px solid #000; padding-top: 6px; text-align: center; }
        .tracking .num { font-size: 18px; font-weight: bold; letter-spacing: 1px; }
        .tracking .lbl { font-size: 8px; text-transform: uppercase; color: #444; }
    </style>
</head>
<body>
    <div class="carrier">$Carrier.XML<% if not $Carrier %>$Provider.XML<% end_if %></div>
    <% if $Service %><div class="service">$Service.XML</div><% end_if %>

    <% with $Order %>
    <div class="meta">
        <%t SilverShop\ShippingLabels\ShippingLabelDocument.ORDER 'Order' %> $Reference
    </div>
    <% end_with %>

    <div class="box">
        <h3><%t SilverShop\ShippingLabels\ShippingLabelDocument.FROM 'From' %></h3>
        <% with $SiteConfig %>
            $SenderName<% if $SenderStreet %>, $SenderStreet<% end_if %><% if $SenderPostcode || $SenderCity %>, $SenderPostcode $SenderCity<% end_if %><% if $SenderCountry %>, $SenderCountry<% end_if %>
        <% end_with %>
    </div>

    <div class="box">
        <h3><%t SilverShop\ShippingLabels\ShippingLabelDocument.SHIPTO 'Ship to' %></h3>
        <% with $Order.ShippingAddress %>
        <div class="to">
            <span class="name">$Name</span><br>
            <% if $Company %>$Company<br><% end_if %>
            $Address<br>
            <% if $AddressLine2 %>$AddressLine2<br><% end_if %>
            $PostalCode $City<br>
            <% if $State %>$State<br><% end_if %>
            $Country
        </div>
        <% end_with %>
    </div>

    <div class="tracking">
        <div class="lbl"><%t SilverShop\ShippingLabels\ShippingLabelDocument.TRACKING 'Tracking number' %></div>
        <div class="num"><% if $TrackingNumber %>$TrackingNumber.XML<% else %>&mdash;<% end_if %></div>
    </div>
</body>
</html>
