<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Extension;

use RuntimeException;
use SilverShop\ShippingLabels\Model\ShippingLabel;
use SilverShop\ShippingLabels\Provider\ProviderRegistry;
use SilverShop\ShippingLabels\Provider\ShippingLabelResult;
use SilverStripe\Assets\File;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Security;
use Throwable;

/**
 * Applied to {@link \SilverShop\Model\Order}. Adds the ShippingLabels relation and a helper to create a label via a
 * {@link \SilverShop\ShippingLabels\Provider\ShippingLabelProvider}.
 *
 * @property \SilverShop\Model\Order $owner
 * @method HasManyList<ShippingLabel> ShippingLabels()
 */
class OrderLabelExtension extends Extension
{
    private static array $has_many = [
        'ShippingLabels' => ShippingLabel::class . '.Order',
    ];

    private static array $cascade_deletes = [
        'ShippingLabels',
    ];

    /**
     * Create a shipping label for this order with the named provider (or the shop default). Provider/API failures
     * are caught and recorded on the label as Status = Error, so the merchant sees them rather than a white screen.
     *
     * @param array<string, mixed> $options passed through to the provider
     */
    public function createShippingLabel(?string $provider = null, array $options = []): ShippingLabel
    {
        $registry = Injector::inst()->get(ProviderRegistry::class);
        $service = $provider ? $registry->get($provider) : $registry->getDefault();
        if (!$service) {
            throw new RuntimeException('No shipping label provider is configured.');
        }

        $label = ShippingLabel::create();
        $label->OrderID = $this->getOwner()->ID;
        $label->Provider = $service->getName();
        if ($member = Security::getCurrentUser()) {
            $label->CreatedByID = $member->ID;
        }

        try {
            $this->applyResult($label, $service->createLabel($this->getOwner(), $options));
            $label->Status = 'Created';
        } catch (Throwable $e) {
            $label->Status = 'Error';
            $label->Message = $e->getMessage();
        }

        $label->write();

        return $label;
    }

    private function applyResult(ShippingLabel $label, ShippingLabelResult $result): void
    {
        $label->Carrier = $result->carrier;
        $label->Service = $result->service;
        $label->TrackingNumber = $result->trackingNumber;
        $label->TrackingUrl = $result->trackingUrl;
        $label->ProviderRef = $result->providerRef;
        if ($result->cost !== null) {
            $label->Cost = $result->cost;
        }

        if ($result->pdfBytes !== '') {
            $reference = $this->getOwner()->Reference ?: (string) $this->getOwner()->ID;
            $file = File::create();
            $file->setFromString($result->pdfBytes, 'shipping-labels/label-' . $reference . '-' . uniqid() . '.pdf');
            $file->write();
            $file->publishSingle();
            $label->LabelFileID = $file->ID;
        }
    }

    public function getPrimaryShippingLabel(): ?ShippingLabel
    {
        return $this->getOwner()->ShippingLabels()->filter('Status', 'Created')->first();
    }

    public function getTrackingNumber(): string
    {
        $label = $this->getPrimaryShippingLabel();

        return $label ? (string) $label->TrackingNumber : '';
    }

    public function getTrackingUrl(): string
    {
        $label = $this->getPrimaryShippingLabel();

        return $label ? (string) $label->TrackingUrl : '';
    }
}
