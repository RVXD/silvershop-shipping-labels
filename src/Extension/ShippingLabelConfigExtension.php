<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Extension;

use SilverShop\ShippingLabels\Provider\ProviderRegistry;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;

/**
 * Applied to {@link \SilverStripe\SiteConfig\SiteConfig}. Adds a "Shipping labels" area to Settings > Shop: the
 * default provider and the shop's sender / return address (required on carrier labels; silvershop has no shop
 * address of its own). Secrets (API keys) are NOT stored here — they come from the environment (.env).
 *
 * @property \SilverStripe\SiteConfig\SiteConfig $owner
 * @property string $DefaultShippingProvider
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

        $fields->addFieldsToTab($tab, [
            DropdownField::create(
                'DefaultShippingProvider',
                _t(self::class . '.DefaultProvider', 'Default label provider'),
                $providers
            )->setEmptyString(_t(self::class . '.None', '(none)')),
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
}
