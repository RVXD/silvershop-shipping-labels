<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Control;

use SilverShop\Model\Order;
use SilverShop\ShippingLabels\PackingSlipService;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;

/**
 * Streams staff-facing order documents (the packing slip) by order ID, gated on the order's edit permission. A
 * dedicated route so it can be opened in a new tab from the CMS without relying on GridField action routing.
 */
class ShippingLabelController extends Controller
{
    private static string $url_segment = 'shippinglabels';

    private static array $allowed_actions = [
        'packingslip',
    ];

    private static array $url_handlers = [
        'packingslip/$ID' => 'packingslip',
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
}
