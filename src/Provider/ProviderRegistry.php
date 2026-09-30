<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Provider;

use InvalidArgumentException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Registry of the shipping label providers available to the shop. Providers are declared in config (name => Injector
 * service) so additional carriers/adapters plug in without code changes:
 *
 *   SilverShop\ShippingLabels\Provider\ProviderRegistry:
 *     providers:
 *       myparcel: '%$SilverShop\ShippingMyParcel\MyParcelProvider'
 */
class ProviderRegistry
{
    use Configurable;

    /**
     * @var array<string, string> provider name => Injector service name / class
     */
    private static array $providers = [];

    /**
     * @return array<string, ShippingLabelProvider> keyed by provider name
     */
    public function all(): array
    {
        $providers = [];
        foreach ((array) self::config()->get('providers') as $service) {
            if (!$service) {
                continue;
            }
            /** @var ShippingLabelProvider $provider */
            $provider = Injector::inst()->get($service);
            $providers[$provider->getName()] = $provider;
        }

        return $providers;
    }

    public function get(string $name): ShippingLabelProvider
    {
        $providers = $this->all();
        if (!isset($providers[$name])) {
            throw new InvalidArgumentException("Unknown shipping label provider: {$name}");
        }

        return $providers[$name];
    }

    /**
     * The default provider: the one selected in SiteConfig, else the first configured provider, else the first
     * registered. Null when none are registered.
     */
    public function getDefault(): ?ShippingLabelProvider
    {
        $providers = $this->all();
        if (!$providers) {
            return null;
        }

        $chosen = (string) SiteConfig::current_site_config()->DefaultShippingProvider;
        if ($chosen && isset($providers[$chosen])) {
            return $providers[$chosen];
        }

        foreach ($providers as $provider) {
            if ($provider->isConfigured()) {
                return $provider;
            }
        }

        return reset($providers);
    }

    /**
     * [name => title] for CMS dropdowns.
     *
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];
        foreach ($this->all() as $name => $provider) {
            $options[$name] = $provider->getTitle();
        }

        return $options;
    }
}
