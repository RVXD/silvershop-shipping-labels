<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Thin wrapper around Dompdf with the module's defaults (no remote assets, an embedded unicode font). Shared by the
 * packing slip and shipping-label document renderers so the PDF setup lives in one place.
 */
class PdfRenderer
{
    /**
     * @param string|float[] $paper a named size ('A4') or a [x0, y0, x1, y1] point rectangle (e.g. a 4x6" label)
     */
    public static function render(string $html, string|array $paper = 'A4', string $orientation = 'portrait'): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
