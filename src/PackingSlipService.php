<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels;

use SilverShop\Model\Order;

/**
 * Renders an order's packing slip (contents to pack, no prices) to HTML and PDF. A separate, print-on-demand
 * internal document — not stored (unlike the carrier label).
 */
class PackingSlipService
{
    public function html(Order $order): string
    {
        return (string) $order->renderWith('SilverShop\\ShippingLabels\\PackingSlip');
    }

    public function pdf(Order $order): string
    {
        return PdfRenderer::render($this->html($order), 'A4', 'portrait');
    }
}
