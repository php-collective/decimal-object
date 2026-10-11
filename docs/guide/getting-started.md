# Getting started

Floats cannot represent most decimal fractions exactly:

```php
var_dump(0.1 + 0.2 === 0.3); // bool(false)
var_dump(0.1 + 0.2 - 0.3);   // float(5.551115123125783E-17)
```

`Decimal` stores the number as a string of digits and calculates with bcmath, so the result is the one you would get on paper:

```php
use PhpCollective\DecimalObject\Decimal;

$sum = Decimal::create('0.1')->add('0.2');

echo $sum;                  // 0.3
var_dump($sum->equals('0.3')); // bool(true)
```

## Immutability

A `Decimal` never changes. Each operation returns a new object, so assign the result:

```php
$price = Decimal::create('19.90');
$total = $price->multiply('3');

echo $price; // 19.90
echo $total; // 59.70
```

## A small invoice

```php
$net = Decimal::create('19.99');
$tax = $net->multiply('0.19')->round(2); // 3.80
$gross = $net->add($tax);                // 23.79

if ($gross->greaterThan('20')) {
    echo 'Total: ' . $gross;
}
```

`multiply()` returned `3.7981` here, with the scales of both operands added up. `round(2)` brings it back to two decimal places. [Precision and scale](./scale) explains the rules for each operation.

## Use strings for input

Pass decimal strings whenever you can. A float has already lost digits before the object sees it:

```php
echo Decimal::create('1234567890.1234567'); // 1234567890.1234567
echo Decimal::create(1234567890.1234567);   // 1234567890.1235
```

See [Creating decimals](./creating) for all accepted input.

## Where to go next

- [Arithmetic](./arithmetic) for the math operations
- [Rounding](./rounding) for `round()`, `floor()`, `ceil()` and `truncate()`
- [Comparison and checks](./comparison)
- [API reference](../reference/api) for every method on one page
