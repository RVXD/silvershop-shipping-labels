<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Provider;

/**
 * Immutable result of a provider's createLabel() call: the label PDF bytes (may be empty for the Manual provider)
 * plus carrier/tracking metadata.
 */
class ShippingLabelResult
{
    public function __construct(
        public readonly string $pdfBytes = '',
        public readonly string $trackingNumber = '',
        public readonly string $trackingUrl = '',
        public readonly string $carrier = '',
        public readonly string $service = '',
        public readonly string $providerRef = '',
        public readonly ?float $cost = null,
    ) {
    }
}
