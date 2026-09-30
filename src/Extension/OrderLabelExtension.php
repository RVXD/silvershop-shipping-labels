<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Extension;

use LeKoala\CmsActions\CustomLink;
use RuntimeException;
use SilverShop\ShippingLabels\Model\ShippingLabel;
use SilverShop\ShippingLabels\Provider\ProviderRegistry;
use SilverShop\ShippingLabels\Provider\ShippingLabelResult;
use SilverStripe\Assets\File;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
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
     * Add a "Shipping labels" tab to the order edit form: an editable grid of the order's labels (add / edit / delete
     * as ordinary records) plus a "Packing slip" tab previewing the packing slip PDF inline. The "Print packing slip"
     * button is added in {@link self::updateCMSActions()}.
     */
    public function updateCMSFields(FieldList $fields): void
    {
        $grid = GridField::create(
            'ShippingLabels',
            _t(self::class . '.ShippingLabels', 'Shipping labels'),
            $this->getOwner()->ShippingLabels(),
            GridFieldConfig_RecordEditor::create()
        );

        $fields->addFieldToTab('Root.ShippingLabels', $grid);

        // Preview the packing slip PDF inline (saved orders only — the route needs an order ID).
        if ($this->getOwner()->isInDB()) {
            $slipUrl = Director::absoluteURL('shippinglabels/packingslip/' . $this->getOwner()->ID);
            $fields->findOrMakeTab('Root.PackingSlip', _t(self::class . '.PackingSlipTab', 'Packing slip'));
            $fields->addFieldToTab(
                'Root.PackingSlip',
                LiteralField::create(
                    'PackingSlipPreview',
                    '<iframe src="' . Convert::raw2att($slipUrl) . '" '
                    . 'style="width:100%;height:80vh;min-height:600px;border:1px solid #ced5e1;border-radius:4px;" '
                    . 'title="' . Convert::raw2att(_t(self::class . '.PackingSlipTab', 'Packing slip')) . '"></iframe>'
                )
            );
        }
    }

    /**
     * Add a "Print packing slip" button to the order edit-form action bar via lekoala/silverstripe-cms-actions. It
     * opens the packing slip PDF in a new tab via {@link \SilverShop\ShippingLabels\Control\ShippingLabelController}.
     */
    public function updateCMSActions(FieldList $actions): void
    {
        $order = $this->getOwner();
        if (!$order->isInDB()) {
            return;
        }

        $actions->push(
            CustomLink::create(
                'printPackingSlip',
                _t(self::class . '.PrintPackingSlip', 'Print packing slip'),
                Director::absoluteURL('shippinglabels/packingslip/' . $order->ID)
            )
                ->setButtonIcon('print')
                ->setNewWindow(true)
        );
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
