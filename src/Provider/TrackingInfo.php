<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Provider;

/**
 * Immutable tracking snapshot returned by a provider's track().
 */
class TrackingInfo
{
    /**
     * @param array<int, array{date: string, status: string}> $events
     */
    public function __construct(
        public readonly string $status,
        public readonly string $trackingNumber = '',
        public readonly string $trackingUrl = '',
        public readonly array $events = [],
    ) {
    }
}
