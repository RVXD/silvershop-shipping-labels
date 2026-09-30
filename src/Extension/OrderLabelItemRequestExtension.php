<?php

declare(strict_types=1);

namespace SilverShop\ShippingLabels\Extension;

use SilverShop\Model\Order;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;

/**
 * Applied to {@link \SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest} (all ModelAdmin edit forms). For
 * Order records it adds a "Create shipping label" action that consumes the new-label inputs added by
 * {@link OrderLabelExtension::updateCMSFields()} and calls Order::createShippingLabel().
 *
 * @property \SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest $owner
 */
class OrderLabelItemRequestExtension extends Extension
{
    private static array $allowed_actions = [
        'doCreateShippingLabel',
    ];

    public function updateItemEditForm(Form $form): void
    {
        if (!($this->getOwner()->getRecord() instanceof Order)) {
            return;
        }

        $form->Actions()->push(
            FormAction::create('doCreateShippingLabel', _t(self::class . '.CreateLabel', 'Create shipping label'))
                ->setUseButtonTag(true)
                ->addExtraClass('btn btn-outline-primary font-icon-truck')
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function doCreateShippingLabel(array $data, Form $form): HTTPResponse
    {
        $record = $this->getOwner()->getRecord();
        if (!($record instanceof Order)) {
            return $this->getOwner()->httpError(400);
        }

        $provider = !empty($data['NewLabelProvider']) ? (string) $data['NewLabelProvider'] : null;
        $label = $record->createShippingLabel($provider, [
            'Carrier' => (string) ($data['NewLabelCarrier'] ?? ''),
            'TrackingNumber' => (string) ($data['NewLabelTracking'] ?? ''),
            'TrackingUrl' => (string) ($data['NewLabelTrackingUrl'] ?? ''),
        ]);

        if ($label->Status === 'Error') {
            $form->sessionMessage(
                _t(
                    self::class . '.CreateFailed',
                    'Could not create the shipping label: {message}',
                    ['message' => $label->Message]
                ),
                'bad'
            );
        } else {
            $form->sessionMessage(_t(self::class . '.Created', 'Shipping label created.'), 'good');
        }

        return $this->getOwner()->edit($this->getOwner()->getRequest());
    }
}
