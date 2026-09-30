<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Provider;

use SilverShop\Model\Order;
use SilverShop\ShippingLabels\Model\ShippingLabel;

/**
 * A no-API provider: the merchant records a tracking number (and optionally uploads a label PDF) for a shipment they
 * created out-of-band with a carrier. Always available — the baseline provider and fallback.
 */
class ManualProvider implements ShippingLabelProvider
{
    public function getName(): string
    {
        return 'manual';
    }

    public function getTitle(): string
    {
        return _t(self::class . '.Title', 'Manual (enter tracking / upload a label)');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getServiceOptions(): array
    {
        return [];
    }

    public function createLabel(Order $order, array $options = []): ShippingLabelResult
    {
        return new ShippingLabelResult(
            trackingNumber: (string) ($options['TrackingNumber'] ?? ''),
            trackingUrl: (string) ($options['TrackingUrl'] ?? ''),
            carrier: (string) ($options['Carrier'] ?? ''),
        );
    }

    public function cancelLabel(ShippingLabel $label): bool
    {
        return true;
    }

    public function track(ShippingLabel $label): ?TrackingInfo
    {
        return null;
    }
}
