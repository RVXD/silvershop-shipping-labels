<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels;

use SilverShop\ShippingLabels\Model\ShippingLabel;

/**
 * Renders a basic shipping label (sender, recipient, carrier, tracking) to HTML and PDF for a {@link ShippingLabel}.
 * Used for the Manual provider, which has no carrier API to fetch a real label from; carrier integrations store their
 * own PDF on the label's LabelFile instead. Sized for a 4x6" (288x432pt) shipping label.
 */
class LabelPdfService
{
    /** 4x6 inch at 72 points per inch. */
    private const PAPER = [0.0, 0.0, 288.0, 432.0];

    public function html(ShippingLabel $label): string
    {
        return (string) $label->renderWith('SilverShop\\ShippingLabels\\ShippingLabelDocument');
    }

    public function pdf(ShippingLabel $label): string
    {
        return PdfRenderer::render($this->html($label), self::PAPER, 'portrait');
    }
}
