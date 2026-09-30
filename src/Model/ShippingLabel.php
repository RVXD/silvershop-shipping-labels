<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Model;

use SilverShop\Model\Order;
use SilverShop\ShippingLabels\Provider\ProviderRegistry;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * A shipping label created for an order: the provider/carrier, tracking, status and the label PDF (when the provider
 * returned one, or when a carrier PDF is attached manually). An order may have several (reprints, retries,
 * multi-parcel). Records are edited as ordinary GridField rows on the order's "Shipping labels" tab.
 *
 * @property string $Provider
 * @property string $Carrier
 * @property string $Service
 * @property string $TrackingNumber
 * @property string $TrackingUrl
 * @property string $ProviderRef
 * @property string $Status
 * @property string $Message
 * @property int $OrderID
 * @property int $LabelFileID
 * @method Order Order()
 * @method File LabelFile()
 * @method Member CreatedBy()
 */
class ShippingLabel extends DataObject
{
    private static string $table_name = 'SilverShop_ShippingLabel';

    private static array $db = [
        'Provider' => 'Varchar(50)',
        'Carrier' => 'Varchar(100)',
        'Service' => 'Varchar(100)',
        'TrackingNumber' => 'Varchar(100)',
        'TrackingUrl' => 'Varchar(512)',
        'ProviderRef' => 'Varchar(255)',
        'Status' => "Enum('Created,Cancelled,Error', 'Created')",
        'Message' => 'Text',
        'Weight' => 'Decimal(9,3)',
        'Cost' => 'Currency',
    ];

    private static array $has_one = [
        'Order' => Order::class,
        'LabelFile' => File::class,
        'CreatedBy' => Member::class,
    ];

    private static array $owns = [
        'LabelFile',
    ];

    private static array $summary_fields = [
        'Created.Nice' => 'Created',
        'Provider' => 'Provider',
        'Carrier' => 'Carrier',
        'TrackingNumber' => 'Tracking #',
        'Status' => 'Status',
    ];

    private static string $default_sort = 'Created DESC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // The order is the GridField parent; its foreign key is set automatically.
        $fields->removeByName('OrderID');

        // Offer the configured providers rather than a free-text code.
        $providers = Injector::inst()->get(ProviderRegistry::class)->options();
        $fields->replaceField(
            'Provider',
            DropdownField::create('Provider', $this->fieldLabel('Provider'), $providers)
                ->setEmptyString(_t(self::class . '.DefaultProvider', '(shop default)'))
                ->setDescription(_t(self::class . '.ProviderDesc', 'Carrier integration this label belongs to.'))
        );

        // Restrict the attached label to a PDF in a dedicated folder.
        $labelFile = $fields->dataFieldByName('LabelFile');
        if ($labelFile instanceof UploadField) {
            $labelFile->setFolderName('shipping-labels');
            $labelFile->getValidator()->setAllowedExtensions(['pdf']);
        }

        // Provider-managed fields: visible but not hand-edited.
        foreach (['ProviderRef', 'Message', 'CreatedByID'] as $name) {
            if ($field = $fields->dataFieldByName($name)) {
                $fields->replaceField($name, $field->performReadonlyTransformation());
            }
        }

        // Preview the label PDF (the uploaded/carrier file, or one rendered on the fly for the Manual provider).
        if ($this->isInDB()) {
            $url = Director::absoluteURL('shippinglabels/label/' . $this->ID);
            $fields->findOrMakeTab('Root.LabelPreview', _t(self::class . '.LabelTab', 'Label'));
            $fields->addFieldToTab(
                'Root.LabelPreview',
                LiteralField::create(
                    'LabelPreview',
                    '<iframe src="' . Convert::raw2att($url) . '" '
                    . 'style="width:100%;height:80vh;min-height:520px;border:1px solid #ced5e1;border-radius:4px;" '
                    . 'title="' . Convert::raw2att(_t(self::class . '.LabelTab', 'Label')) . '"></iframe>'
                )
            );
        }

        return $fields;
    }

    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        if (!$this->Status) {
            $this->Status = 'Created';
        }
        if (!$this->CreatedByID && ($member = Security::getCurrentUser())) {
            $this->CreatedByID = $member->ID;
        }
    }

    public function canView($member = null): bool
    {
        $order = $this->Order();

        return $order && $order->exists()
            ? $order->canView($member)
            : Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function canEdit($member = null): bool
    {
        $order = $this->Order();

        return $order && $order->exists()
            ? $order->canEdit($member)
            : Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    public function canDelete($member = null): bool
    {
        return $this->canEdit($member);
    }

    public function getTitle(): string
    {
        $label = trim(($this->Carrier ?: $this->Provider) . ' ' . $this->TrackingNumber);

        return $label !== '' ? $label : 'Shipping label #' . $this->ID;
    }

    public function hasLabelFile(): bool
    {
        return $this->LabelFileID && $this->LabelFile()->exists();
    }
}
