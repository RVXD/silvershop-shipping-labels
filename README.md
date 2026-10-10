# SilverShop Shipping Labels

Carrier-connected **shipping label** generation for [SilverShop](https://github.com/silvershop/silvershop-core), with a
pluggable **provider** layer so multiple shipping services can be connected.

SilverShop can calculate shipping *cost* (`silvershop/shipping`) but has no way to create the label you stick on the
parcel. This module adds that: a `ShippingLabel` record per order (carrier, tracking, status, and the label PDF), a
provider interface that carriers/aggregators implement, and CMS actions to create and print labels.

## What it adds

- A **`ShippingLabelProvider`** interface + a config-driven **`ProviderRegistry`**, so providers plug in without code
  changes and are selectable per order.
- A **Manual** provider (built in): record a tracking number / upload a label PDF for a shipment created out-of-band.
- A **`ShippingLabel`** model (immutable; stores the PDF as a `File`) — `Order has_many ShippingLabels`.
- **Settings → Shop → Shipping labels**: the default provider + the shop's **sender / return address**.
- CMS actions to create and print a label (single label per order; bulk/batch printing is planned).

Carrier adapters ship as separate packages, e.g. **`silvershop/shipping-myparcel`** (MyParcel → PostNL/DHL). Add more
by implementing `ShippingLabelProvider` and registering it:

```yaml
SilverShop\ShippingLabels\Provider\ProviderRegistry:
  providers:
    myparcel: 'SilverShop\ShippingMyParcel\MyParcelProvider'
```

## Installation

```bash
composer require silvershop/shipping-labels
```

Then run `dev/build?flush=all`. Provider **API keys come from the environment** (`.env`), never from config/SiteConfig.

## Usage

```php
// Create a label for an order with the default provider (or pass a provider name).
$label = $order->createShippingLabel();                     // default provider
$label = $order->createShippingLabel('manual', ['TrackingNumber' => '3STBJG...']);

$order->getTrackingNumber();      // '' or the primary label's tracking number
$order->ShippingLabels();         // all labels for the order
```

## Licence

BSD-3-Clause.
