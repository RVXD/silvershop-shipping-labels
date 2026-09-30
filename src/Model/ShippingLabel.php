<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Model;

use SilverShop\Model\Order;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * An immutable record of a shipping label created for an order: the provider/carrier, tracking, status and the label
 * PDF (when the provider returned one). An order may have several (reprints, retries, multi-parcel).
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

    /**
     * Labels are immutable once created; use cancel instead of editing.
     */
    public function canEdit($member = null): bool
    {
        return false;
    }

    public function canView($member = null): bool
    {
        return $this->Order()->canView($member);
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
