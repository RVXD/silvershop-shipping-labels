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
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
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
     * Add a "Shipping labels" tab to the order edit form: the order's labels (view + delete + download the PDF via
     * the record view) plus the inputs for a new label. The "Create shipping label" action button that consumes
     * these inputs is added by {@link OrderLabelItemRequestExtension}.
     */
    public function updateCMSFields(FieldList $fields): void
    {
        $providers = Injector::inst()->get(ProviderRegistry::class)->options();

        $grid = GridField::create(
            'ShippingLabels',
            _t(self::class . '.ShippingLabels', 'Shipping labels'),
            $this->getOwner()->ShippingLabels(),
            GridFieldConfig_RecordViewer::create()->addComponent(GridFieldDeleteAction::create())
        );

        $create = ToggleCompositeField::create(
            'NewShippingLabel',
            _t(self::class . '.CreateLabel', 'Create a shipping label'),
            [
                DropdownField::create('NewLabelProvider', _t(self::class . '.Provider', 'Provider'), $providers)
                    ->setEmptyString(_t(self::class . '.DefaultProvider', '(shop default)')),
                TextField::create('NewLabelCarrier', _t(self::class . '.Carrier', 'Carrier')),
                TextField::create('NewLabelTracking', _t(self::class . '.Tracking', 'Tracking number')),
                TextField::create('NewLabelTrackingUrl', _t(self::class . '.TrackingUrl', 'Tracking URL')),
            ]
        );

        $fields->addFieldsToTab('Root.ShippingLabels', [$create, $grid]);
    }

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
