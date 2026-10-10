<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Tests;

use SilverShop\Model\Order;
use SilverShop\ShippingLabels\Model\ShippingLabel;
use SilverShop\ShippingLabels\Report\UnshippedOrdersReport;
use SilverStripe\Dev\SapphireTest;

class UnshippedOrdersReportTest extends SapphireTest
{
    protected $usesDatabase = true;

    /**
     * The report instantiates, has a title, and its hand-written SQL (raw SQLSelect with a NOT EXISTS
     * sub-select and a LEFT JOIN) executes cleanly against an empty database — which in CI is SQLite.
     */
    public function testReportRunsCleanly(): void
    {
        $report = new UnshippedOrdersReport();

        $this->assertNotEmpty((string) $report->title(), 'report has a title');
        $this->assertCount(0, $report->sourceRecords(), 'report runs cleanly with no data');
    }

    /**
     * A placed order with no shipping label is listed; an otherwise identical order that already has a
     * label is not.
     */
    public function testOnlyOrdersWithoutALabelAreListed(): void
    {
        $unshipped = Order::create(['Reference' => 'UNSHIPPED-1', 'Status' => 'Paid']);
        $unshipped->write();

        $shipped = Order::create(['Reference' => 'SHIPPED-1', 'Status' => 'Paid']);
        $shipped->write();
        $label = ShippingLabel::create([
            'OrderID' => $shipped->ID,
            'Provider' => 'manual',
            'Carrier' => 'PostNL',
            'TrackingNumber' => '3SLABEL123',
        ]);
        $label->write();

        // A cancelled order has no label but must NOT appear in a fulfilment queue.
        $cancelled = Order::create(['Reference' => 'CANCELLED-1', 'Status' => 'AdminCancelled']);
        $cancelled->write();

        $references = (new UnshippedOrdersReport())->sourceRecords()->column('Reference');

        $this->assertContains('UNSHIPPED-1', $references, 'order with no label is listed');
        $this->assertNotContains('SHIPPED-1', $references, 'order with a label is not listed');
        $this->assertNotContains('CANCELLED-1', $references, 'cancelled order is excluded from the queue');
    }
}
