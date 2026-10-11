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

Each method accepts a `Decimal`, a numeric string or an integer.

`compareTo()` returns `-1`, `0` or `1` and fits `usort()`:

```php
$values = [Decimal::create('2.5'), Decimal::create('-1'), Decimal::create('2.05')];

usort($values, fn (Decimal $a, Decimal $b) => $a->compareTo($b));
// -1, 2.05, 2.5
```

## Ranges

```php
var_dump(Decimal::create('5')->between(1, 10));        // bool(true)
var_dump(Decimal::create('5')->between(5, 10, false)); // bool(false)

echo Decimal::create('12')->clamp(0, 10); // 10
echo Decimal::create('-3')->clamp(0, 10); // 0
```

`between()` includes both bounds unless the third argument is `false`. `clamp()` returns the nearest bound for a value outside the range and throws an `InvalidArgumentException` when the lower bound is above the upper one.

> [!TIP] Added in 1.4

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

`isEven()` and `isOdd()` look at whole numbers of any size. Both are false for a value with decimals:

```php
var_dump(Decimal::create('4.00')->isEven()); // bool(true)
var_dump(Decimal::create('7')->isOdd());     // bool(true)
var_dump(Decimal::create('2.5')->isEven());  // bool(false)
```

> [!TIP] Added in 1.4
> `isEven()` and `isOdd()`.
