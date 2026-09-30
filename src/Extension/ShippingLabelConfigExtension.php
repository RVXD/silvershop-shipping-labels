<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Extension;

use SilverShop\ShippingLabels\Provider\ProviderRegistry;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Applied to {@link \SilverStripe\SiteConfig\SiteConfig}. Adds a "Shipping labels" area to Settings > Shop: the
 * default provider and (as a fallback) the shop's sender / return address. When silvershop/store-profile is installed
 * the sender comes from the central store profile and these fields are hidden. Secrets (API keys) are NOT stored here
 * — they come from the environment (.env).
 *
 * @property \SilverStripe\SiteConfig\SiteConfig $owner
 * @property string $DefaultShippingProvider
 * @property string $SenderName
 * @property string $SenderStreet
 * @property string $SenderPostcode
 * @property string $SenderCity
 * @property string $SenderCountry
 * @property string $SenderPhone
 * @property string $SenderEmail
 */
class ShippingLabelConfigExtension extends Extension
{
    private static array $db = [
        'DefaultShippingProvider' => 'Varchar(50)',
        'SenderName' => 'Varchar(255)',
        'SenderStreet' => 'Varchar(255)',
        'SenderPostcode' => 'Varchar(20)',
        'SenderCity' => 'Varchar(255)',
        'SenderCountry' => 'Varchar(2)',
        'SenderPhone' => 'Varchar(50)',
        'SenderEmail' => 'Varchar(255)',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $providers = Injector::inst()->get(ProviderRegistry::class)->options();

        $tab = $fields->fieldByName('Root.Shop') ? 'Root.Shop.ShopTabs.ShippingLabels' : 'Root.ShippingLabels';

        $fields->addFieldToTab($tab, DropdownField::create(
            'DefaultShippingProvider',
            _t(self::class . '.DefaultProvider', 'Default label provider'),
            $providers
        )->setEmptyString(_t(self::class . '.None', '(none)')));

        // The sender address comes from silvershop/store-profile when it is installed; otherwise fall back to our own
        // fields so the module still works standalone.
        if ($this->usesStoreProfile()) {
            $fields->addFieldToTab($tab, LiteralField::create(
                'SenderFromProfile',
                '<p class="message notice">' . _t(
                    self::class . '.SenderFromProfile',
                    'The sender / return address comes from the <strong>Store profile</strong> tab.'
                ) . '</p>'
            ));

            return;
        }

        $fields->addFieldsToTab($tab, [
            HeaderField::create('SenderHeader', _t(self::class . '.SenderAddress', 'Sender / return address')),
            TextField::create('SenderName', _t(self::class . '.SenderName', 'Name')),
            TextField::create('SenderStreet', _t(self::class . '.SenderStreet', 'Street + number')),
            TextField::create('SenderPostcode', _t(self::class . '.SenderPostcode', 'Postcode')),
            TextField::create('SenderCity', _t(self::class . '.SenderCity', 'City')),
            TextField::create('SenderCountry', _t(self::class . '.SenderCountry', 'Country (ISO 3166-1 alpha-2)'))
                ->setMaxLength(2),
            TextField::create('SenderPhone', _t(self::class . '.SenderPhone', 'Phone')),
            TextField::create('SenderEmail', _t(self::class . '.SenderEmail', 'Email')),
        ]);
    }

    /**
     * The sender name for labels/slips: the central store profile if present, else our own field, else the site title.
     */
    public function LabelSenderName(): string
    {
        $owner = $this->getOwner();
        if ($this->usesStoreProfile()) {
            $name = (string) $owner->StoreProfileName();
            if ($name !== '') {
                return $name;
            }
        }

        return (string) ($owner->SenderName ?: $owner->Title);
    }

    /**
     * The sender address as HTML lines: the central store profile (country-formatted) if present, else built from our
     * own fields.
     */
    public function LabelSenderAddressHTML(): DBHTMLText
    {
        $owner = $this->getOwner();

        if ($this->usesStoreProfile() && $owner->hasStoreAddress()) {
            return DBField::create_field('HTMLFragment', $owner->StoreFormattedAddress(true));
        }

        $lines = array_values(array_filter([
            trim((string) $owner->SenderStreet),
            trim(trim((string) $owner->SenderPostcode) . ' ' . trim((string) $owner->SenderCity)),
            trim((string) $owner->SenderCountry),
        ], fn ($line) => $line !== ''));

        $html = implode('<br>', array_map(fn ($line) => Convert::raw2xml($line), $lines));

        return DBField::create_field('HTMLFragment', $html);
    }

    private function usesStoreProfile(): bool
    {
        return $this->getOwner()->hasMethod('StoreProfileName');
    }
}
