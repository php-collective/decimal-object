# Rounding

```php
use PhpCollective\DecimalObject\Decimal;

$value = Decimal::create('12.375');
```

## round()

```php
echo $value->round(2);                     // 12.38
echo Decimal::create('12.374')->round(2);  // 12.37
echo Decimal::create('12.5')->round();     // 13
echo Decimal::create('-12.5')->round();    // -13
echo Decimal::create('11')->round(2);      // 11.00
```

The first argument is the number of decimal places, `0` by default. For non-negative scales, the half-rounding modes pad the result with zeros if needed. Negative scales round to positions before the decimal point. `ROUND_FLOOR` and `ROUND_CEIL` have the legacy behavior described below.

The default mode rounds half away from zero, like PHP's `round()`. It works on the decimal digits and never passes through a float.

## Rounding modes

Pass a mode as second argument. All of these round at the given scale:

| Constant | Direction | `2.5` | `3.5` | `-2.5` | `2.1` |
| --- | --- | --- | --- | --- | --- |
| `Decimal::ROUND_HALF_UP` | nearest, ties away from zero (default) | `3` | `4` | `-3` | `2` |
| `Decimal::ROUND_HALF_DOWN` | nearest, ties towards zero | `2` | `3` | `-2` | `2` |
| `Decimal::ROUND_HALF_EVEN` | nearest, ties to the even neighbor | `2` | `4` | `-2` | `2` |
| `Decimal::ROUND_HALF_ODD` | nearest, ties to the odd neighbor | `3` | `3` | `-3` | `2` |
| `Decimal::ROUND_UP` | away from zero | `3` | `4` | `-3` | `3` |
| `Decimal::ROUND_DOWN` | towards zero | `2` | `3` | `-2` | `2` |

```php
echo Decimal::create('0.125')->round(2, Decimal::ROUND_HALF_EVEN); // 0.12
echo Decimal::create('12.375')->round(2, Decimal::ROUND_DOWN);     // 12.37
```

`ROUND_HALF_EVEN` is the banker's rounding used in accounting to avoid a drift over many values.

> [!TIP] Added in 1.4
> `ROUND_HALF_DOWN`, `ROUND_HALF_EVEN`, `ROUND_HALF_ODD`, `ROUND_UP` and `ROUND_DOWN`.

### Floor and ceil modes

| Constant | Direction | `12.375` | `-12.375` |
| --- | --- | --- | --- |
| `Decimal::ROUND_FLOOR` | whole number towards negative infinity | `12` | `-13` |
| `Decimal::ROUND_CEIL` | whole number towards positive infinity | `13` | `-12` |

::: warning Limits in 1.x
With `round()`, `ROUND_FLOOR` and `ROUND_CEIL` always round to a whole number. A scale passed along with them does not move the rounding position. Use [`withScale()`](#withscale) to floor or ceil at a scale.

The constants have their own values. PHP's `PHP_ROUND_HALF_EVEN`, `PHP_ROUND_HALF_ODD` and `PHP_ROUND_HALF_DOWN` are not the same numbers, and `round()` treats any value it does not know as half up. The next major version will reject unknown modes.
:::

## withScale()

Returns the value with exactly the given number of decimal places. Missing places are filled with zeros, surplus places are rounded:

```php
echo Decimal::create('1.5')->withScale(3);   // 1.500
echo Decimal::create('7')->withScale(2);     // 7.00
echo Decimal::create('1.255')->withScale(2); // 1.26
```

Every mode is applied at the scale, including floor and ceil:

```php
echo Decimal::create('12.375')->withScale(1, Decimal::ROUND_FLOOR);  // 12.3
echo Decimal::create('-12.375')->withScale(1, Decimal::ROUND_FLOOR); // -12.4
echo Decimal::create('12.375')->withScale(1, Decimal::ROUND_CEIL);   // 12.4
```

Use it before output or storage when a column or an API expects a fixed number of decimals. An unknown mode throws an `InvalidArgumentException`.

> [!TIP] Added in 1.4

## floor() and ceil()

Shortcuts for the two directed modes at zero decimal places:

```php
echo $value->floor();                      // 12
echo $value->ceil();                       // 13
echo Decimal::create('-12.375')->floor();  // -13
echo Decimal::create('-12.375')->ceil();   // -12
```

## truncate()

Cuts off digits without looking at them, which rounds towards zero:

```php
echo Decimal::create('12.379')->truncate(2); // 12.37
echo Decimal::create('-12.379')->truncate(); // -12
echo Decimal::create('12.3')->truncate(5);   // 12.3
```

`truncate()` never adds zeros. Use `round()` when the result needs a fixed scale.

## Rounding to a step

`roundToStep()` rounds to the nearest multiple of any positive value, such as five cents for cash payments:

```php
echo Decimal::create('1.03')->roundToStep('0.05'); // 1.05
echo Decimal::create('1.02')->roundToStep('0.05'); // 1.00
echo Decimal::create('7.4')->roundToStep('0.25');  // 7.50

echo Decimal::create('1.01')->roundToStep('0.05', Decimal::ROUND_UP); // 1.05
```

The result has the scale of the step. A step of zero or below throws an `InvalidArgumentException`.

> [!TIP] Added in 1.4

## Rounding to tens and hundreds

A negative scale rounds left of the decimal point:

```php
echo Decimal::create('1250')->round(-2);                             // 1300
echo Decimal::create('14.99')->round(-1);                            // 10
echo Decimal::create('1250')->round(-2, Decimal::ROUND_HALF_EVEN);   // 1200
```

This is not available for `ROUND_FLOOR`, `ROUND_CEIL`, `withScale()` and `truncate()`, which throw an `InvalidArgumentException` for a scale below zero.

> [!TIP] Added in 1.4
> `round()` threw for every negative scale before.
