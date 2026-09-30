<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Control;

use SilverShop\Model\Order;
use SilverShop\ShippingLabels\LabelPdfService;
use SilverShop\ShippingLabels\Model\ShippingLabel;
use SilverShop\ShippingLabels\PackingSlipService;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;

/**
 * Streams staff-facing shipping documents (the packing slip by order ID, and a label by label ID) as inline PDFs,
 * gated on the order's permissions. A dedicated route so documents can be opened in a new tab or previewed in an
 * iframe from the CMS without relying on GridField action routing.
 */
class ShippingLabelController extends Controller
{
    private static string $url_segment = 'shippinglabels';

    private static array $allowed_actions = [
        'packingslip',
        'label',
    ];

    private static array $url_handlers = [
        'packingslip/$ID' => 'packingslip',
        'label/$ID' => 'label',
    ];

    public function packingslip(HTTPRequest $request): HTTPResponse
    {
        $order = Order::get()->byID((int) $request->param('ID'));
        if (!$order) {
            return $this->httpError(404);
        }
        if (!$order->canEdit()) {
            return $this->httpError(403);
        }

        $pdf = Injector::inst()->get(PackingSlipService::class)->pdf($order);

        $response = HTTPResponse::create($pdf);
        $response->addHeader('Content-Type', 'application/pdf');
        $response->addHeader(
            'Content-Disposition',
            'inline; filename="packing-slip-' . ($order->Reference ?: $order->ID) . '.pdf"'
        );

        return $response;
    }

    public function label(HTTPRequest $request): HTTPResponse
    {
        $label = ShippingLabel::get()->byID((int) $request->param('ID'));
        if (!$label) {
            return $this->httpError(404);
        }
        if (!$label->canView()) {
            return $this->httpError(403);
        }

        // A carrier-supplied or manually uploaded PDF wins; otherwise render our own label (the Manual provider).
        if ($label->hasLabelFile()) {
            $pdf = (string) $label->LabelFile()->getString();
        } else {
            $pdf = Injector::inst()->get(LabelPdfService::class)->pdf($label);
        }

        $response = HTTPResponse::create($pdf);
        $response->addHeader('Content-Type', 'application/pdf');
        $response->addHeader('Content-Disposition', 'inline; filename="shipping-label-' . $label->ID . '.pdf"');

        return $response;
    }
}
