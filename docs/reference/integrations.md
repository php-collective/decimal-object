# Integrations

## CakePHP

[dereuromark/cakephp-decimal](https://dereuromark.github.io/cakephp-decimal/) maps `DECIMAL` database columns to `Decimal` objects in the CakePHP ORM. Entities receive value objects on read, and form input is converted during marshalling.

Follow the plugin's [Installation](https://dereuromark.github.io/cakephp-decimal/guide/installation) for setup.

```php
$order = $orders->get($id);
$order->total = $order->total->add('0.10');
$orders->saveOrFail($order);
```

The plugin documentation also covers forms, localized input and formatting.

## Your own framework

The object needs nothing from a framework. For an ORM or database layer, convert in two places:

- Reading: pass the column value to `Decimal::create()` as the string the driver returns.
- Writing: bind `(string)$decimal` as a string parameter.

Fetch decimals as strings. Some drivers can be configured to return floats, and the precision is gone before the object is created.

For JSON APIs the object serializes to a string on its own. Create it from the string in the request payload on the way in.

## Add yours

Built an integration for another framework or library? Open a pull request against [this page](https://github.com/php-collective/decimal-object/edit/master/docs/reference/integrations.md).
