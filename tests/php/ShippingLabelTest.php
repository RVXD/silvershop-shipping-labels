<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Tests;

use InvalidArgumentException;
use SilverShop\Model\Order;
use SilverShop\ShippingLabels\Model\ShippingLabel;
use SilverShop\ShippingLabels\PackingSlipService;
use SilverShop\ShippingLabels\Provider\ProviderRegistry;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class ShippingLabelTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testManualProviderCreatesLabel(): void
    {
        $order = Order::create();
        $order->write();

        $label = $order->createShippingLabel('manual', [
            'TrackingNumber' => '3STBJG1234567',
            'Carrier' => 'PostNL',
            'TrackingUrl' => 'https://example.test/track/3STBJG1234567',
        ]);

        $this->assertInstanceOf(ShippingLabel::class, $label);
        $this->assertSame('Created', $label->Status);
        $this->assertSame('manual', $label->Provider);
        $this->assertSame('PostNL', $label->Carrier);
        $this->assertSame('3STBJG1234567', $label->TrackingNumber);
        $this->assertSame((int) $order->ID, (int) $label->OrderID);

        $this->assertCount(1, $order->ShippingLabels());
        $this->assertSame('3STBJG1234567', $order->getTrackingNumber());
    }

    public function testRegistryDefaultAndOptions(): void
    {
        $registry = Injector::inst()->get(ProviderRegistry::class);

        $this->assertArrayHasKey('manual', $registry->options());
        $this->assertNotNull($registry->getDefault());
        $this->assertSame('manual', $registry->getDefault()->getName());
    }

    public function testUnknownProviderThrows(): void
    {
        $order = Order::create();
        $order->write();

        $this->expectException(InvalidArgumentException::class);
        $order->createShippingLabel('nope');
    }

    public function testPackingSlipRendersPdf(): void
    {
        $order = Order::create(['Reference' => 'PS-TEST-1']);
        $order->write();

        $pdf = Injector::inst()->get(PackingSlipService::class)->pdf($order);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
