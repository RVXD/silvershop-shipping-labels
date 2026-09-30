<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Provider;

use SilverShop\Model\Order;
use SilverShop\ShippingLabels\Model\ShippingLabel;

/**
 * A shipping label provider connects to a carrier or aggregator (MyParcel, Sendcloud, EasyPost, …) — or, in the case
 * of {@link ManualProvider}, to nothing at all. Implementations are registered in {@link ProviderRegistry} and
 * selected per order.
 */
interface ShippingLabelProvider
{
    /**
     * Short machine name, e.g. 'myparcel'. Stored on the ShippingLabel and used for lookup.
     */
    public function getName(): string;

    /**
     * Human title for the CMS, e.g. 'MyParcel (PostNL/DHL)'.
     */
    public function getTitle(): string;

    /**
     * Whether the provider has the configuration (API key etc.) it needs to create labels.
     */
    public function isConfigured(): bool;

    /**
     * Selectable service levels for the CMS, as [code => label]. Empty when not applicable.
     *
     * @return array<string, string>
     */
    public function getServiceOptions(): array;

    /**
     * Create a label for the order with the carrier and return the raw result (PDF bytes + tracking). The caller
     * ({@link \SilverShop\ShippingLabels\Extension\OrderLabelExtension}) persists it as a ShippingLabel.
     *
     * @param array<string, mixed> $options
     * @throws \RuntimeException on provider/API failure
     */
    public function createLabel(Order $order, array $options = []): ShippingLabelResult;

    /**
     * Cancel/void a previously created label with the carrier. Returns true on success.
     */
    public function cancelLabel(ShippingLabel $label): bool;

    /**
     * Current tracking status, or null when the provider does not support tracking.
     */
    public function track(ShippingLabel $label): ?TrackingInfo;
}
