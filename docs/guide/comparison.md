# Comparison and checks

## Comparing values

Comparison looks at the value only. Scale is ignored:

```php
use PhpCollective\DecimalObject\Decimal;

$value = Decimal::create('1.50');

var_dump($value->equals('1.5'));          // bool(true)
var_dump($value->greaterThan('1.49'));    // bool(true)
var_dump($value->lessThan(2));            // bool(true)
var_dump($value->greaterThanOrEquals('1.5')); // bool(true)
var_dump($value->lessThanOrEquals('1.5'));    // bool(true)
```

Each method accepts a `Decimal`, a numeric string, an integer or a float.

`compareTo()` returns `-1`, `0` or `1` and fits `usort()`:

```php
$values = [Decimal::create('2.5'), Decimal::create('-1'), Decimal::create('2.05')];

usort($values, fn (Decimal $a, Decimal $b) => $a->compareTo($b));
// -1, 2.05, 2.5
```

::: warning Do not use == or ===
`==` on two objects compares their properties, so `1.50 == 1.5` is `false`. `===` compares identity. Use `equals()`.
:::

## Sign and zero

| Method | `-3.2` | `0.00` | `3.2` |
| --- | --- | --- | --- |
| `isNegative()` | `true` | `false` | `false` |
| `isZero()` | `false` | `true` | `false` |
| `isPositive()` | `false` | `false` | `true` |
| `sign()` | `-1` | `0` | `1` |

Zero is neither positive nor negative.

## Whole numbers

`isInteger()` is true when all decimals are zero:

```php
var_dump(Decimal::create('5.00')->isInteger()); // bool(true)
var_dump(Decimal::create('5.01')->isInteger()); // bool(false)
```
