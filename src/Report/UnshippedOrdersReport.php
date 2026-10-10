<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Report;

use SilverShop\Model\Order;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Reports\Report;

/**
 * Placed orders that have no shipping label yet — the fulfilment queue. Uses {@link Order}'s configured
 * placed statuses and lists each order that has no row in SilverShop_ShippingLabel (a NOT EXISTS check),
 * oldest first. An optional placed-date range narrows the window.
 */
class UnshippedOrdersReport extends Report
{
    public function title()
    {
        return _t(__CLASS__ . '.TITLE', 'Unshipped orders (no label yet)');
    }

    public function description()
    {
        return _t(__CLASS__ . '.DESC', 'Placed orders awaiting fulfilment: no shipping label has been created yet. Oldest first.');
    }

    public function group()
    {
        return _t('SilverShop\\Reports.GROUP', 'Shop');
    }

    public function sort()
    {
        return 400;
    }

    public function parameterFields()
    {
        return FieldList::create(
            DateField::create('StartDate', _t(__CLASS__ . '.Start', 'From (placed date)')),
            DateField::create('EndDate', _t(__CLASS__ . '.End', 'To (placed date)'))
        );
    }

    public function sourceRecords($params = null)
    {
        // Placed orders awaiting fulfilment, excluding cancelled ones (they are in placed_status but must not sit in
        // a "to ship" queue).
        $statuses = array_values(array_diff(
            (array) Order::config()->get('placed_status'),
            ['MemberCancelled', 'AdminCancelled']
        ));
        if (empty($statuses)) {
            return ArrayList::create();
        }

        $query = SQLSelect::create();
        $query->setSelect([
            'ID' => '"o"."ID"',
            'Reference' => '"o"."Reference"',
            'Placed' => '"o"."Placed"',
            'Paid' => '"o"."Paid"',
            'Total' => '"o"."Total"',
            'FirstName' => '"o"."FirstName"',
            'Surname' => '"o"."Surname"',
            'Email' => '"o"."Email"',
            'MemberFirstName' => '"m"."FirstName"',
            'MemberSurname' => '"m"."Surname"',
            'MemberEmail' => '"m"."Email"',
        ]);
        $query->setFrom('"SilverShop_Order" AS "o"');
        $query->addLeftJoin('Member', '"m"."ID" = "o"."MemberID"', 'm');

        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $query->addWhere(['"o"."Status" IN (' . $placeholders . ')' => $statuses]);

        // Awaiting fulfilment: no shipping label exists for this order yet.
        $query->addWhere(
            'NOT EXISTS (SELECT 1 FROM "SilverShop_ShippingLabel" AS "sl" WHERE "sl"."OrderID" = "o"."ID")'
        );

        if (!empty($params['StartDate'])) {
            $query->addWhere(['"o"."Placed" >= ?' => $params['StartDate'] . ' 00:00:00']);
        }
        if (!empty($params['EndDate'])) {
            $query->addWhere(['"o"."Placed" <= ?' => $params['EndDate'] . ' 23:59:59']);
        }

        $query->setOrderBy('"o"."Placed"', 'ASC');

        $list = ArrayList::create();
        foreach ($query->execute() as $row) {
            $firstName = $row['FirstName'] ?: ($row['MemberFirstName'] ?? '');
            $surname = $row['Surname'] ?: ($row['MemberSurname'] ?? '');
            $email = $row['Email'] ?: ($row['MemberEmail'] ?? '');
            $name = trim($firstName . ' ' . $surname);

            $list->push(ArrayData::create([
                'Reference' => $row['Reference'] ?: ('#' . $row['ID']),
                'Customer' => $name !== '' ? $name : $email,
                'Placed' => $row['Placed'] ? DBField::create_field('Datetime', $row['Placed'])->Nice() : '',
                'Paid' => $row['Paid'] ? DBField::create_field('Datetime', $row['Paid'])->Nice() : '',
                'Total' => number_format((float) $row['Total'], 2),
            ]));
        }

        return $list;
    }

    public function columns()
    {
        return [
            'Reference' => _t(__CLASS__ . '.ColReference', 'Order'),
            'Customer' => _t(__CLASS__ . '.ColCustomer', 'Customer'),
            'Placed' => _t(__CLASS__ . '.ColPlaced', 'Placed'),
            'Paid' => _t(__CLASS__ . '.ColPaid', 'Paid'),
            'Total' => _t(__CLASS__ . '.ColTotal', 'Total'),
        ];
    }
}
