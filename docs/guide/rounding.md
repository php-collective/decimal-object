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

The first argument is the number of decimal places, `0` by default. The result always has exactly that scale, padded with zeros if needed. Negative scales round to positions before the decimal point.

The default mode rounds half away from zero, like PHP's `round()`. It works on the decimal digits and never passes through a float.

## Rounding modes

Pass a mode as second argument. Every mode rounds at the given scale:

| Constant | Direction | `2.5` | `3.5` | `-2.5` | `2.1` |
| --- | --- | --- | --- | --- | --- |
| `Decimal::ROUND_HALF_UP` | nearest, ties away from zero (default) | `3` | `4` | `-3` | `2` |
| `Decimal::ROUND_HALF_DOWN` | nearest, ties towards zero | `2` | `3` | `-2` | `2` |
| `Decimal::ROUND_HALF_EVEN` | nearest, ties to the even neighbor | `2` | `4` | `-2` | `2` |
| `Decimal::ROUND_HALF_ODD` | nearest, ties to the odd neighbor | `3` | `3` | `-3` | `2` |
| `Decimal::ROUND_UP` | away from zero | `3` | `4` | `-3` | `3` |
| `Decimal::ROUND_DOWN` | towards zero | `2` | `3` | `-2` | `2` |
| `Decimal::ROUND_CEIL` | towards positive infinity | `3` | `4` | `-2` | `3` |
| `Decimal::ROUND_FLOOR` | towards negative infinity | `2` | `3` | `-3` | `2` |
| `Decimal::ROUND_UNNECESSARY` | refuses to round | throws | throws | throws | throws |

```php
echo Decimal::create('0.125')->round(2, Decimal::ROUND_HALF_EVEN); // 0.12
echo Decimal::create('12.375')->round(2, Decimal::ROUND_DOWN);     // 12.37
echo Decimal::create('12.375')->round(1, Decimal::ROUND_FLOOR);    // 12.3
echo Decimal::create('-12.375')->round(1, Decimal::ROUND_FLOOR);   // -12.4
```

`ROUND_HALF_EVEN` is the banker's rounding used in accounting to avoid a drift over many values.

`ROUND_UNNECESSARY` asserts that nothing has to be rounded. It pads and drops zeros, and throws a `RoundingNecessaryException` for anything else. It is the default of the arithmetic methods; `round()` and `withScale()` default to `ROUND_HALF_UP`.

### Native rounding modes

On PHP 8.4 and newer every method that takes a mode also accepts PHP's own `RoundingMode` enum:

```php
echo Decimal::create('2.5')->round(0, \RoundingMode::HalfEven);                 // 2
echo Decimal::create('2')->divide('3', 2, \RoundingMode::HalfAwayFromZero);     // 0.67
```

| `RoundingMode` | Constant |
| --- | --- |
| `HalfAwayFromZero` | `ROUND_HALF_UP` |
| `HalfTowardsZero` | `ROUND_HALF_DOWN` |
| `HalfEven` | `ROUND_HALF_EVEN` |
| `HalfOdd` | `ROUND_HALF_ODD` |
| `AwayFromZero` | `ROUND_UP` |
| `TowardsZero` | `ROUND_DOWN` |
| `PositiveInfinity` | `ROUND_CEIL` |
| `NegativeInfinity` | `ROUND_FLOOR` |

The constants keep working on every supported PHP version, so code written for PHP 8.2 needs no change later.

The constants have their own values. PHP's `PHP_ROUND_HALF_EVEN`, `PHP_ROUND_HALF_ODD` and `PHP_ROUND_HALF_DOWN` are not the same numbers. An unknown mode throws an `InvalidArgumentException`.

> [!TIP] Changed in 2.0
> `ROUND_FLOOR` and `ROUND_CEIL` round at the scale, and unknown modes throw. See [Upgrading](../reference/upgrading#from-1-x-to-2-0).

## withScale()

Returns the value with exactly the given number of decimal places. Missing places are filled with zeros, surplus places are rounded:

```php
echo Decimal::create('1.5')->withScale(3);   // 1.500
echo Decimal::create('7')->withScale(2);     // 7.00
echo Decimal::create('1.255')->withScale(2); // 1.26
```

It accepts the same modes as `round()` and returns the same result. The name states the intent when the point is a fixed scale and not the rounding, and a negative scale is rejected.

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

`withScale()` and `truncate()` throw an `InvalidArgumentException` for a scale below zero.

> [!TIP] Added in 1.4
> `round()` threw for every negative scale before.
