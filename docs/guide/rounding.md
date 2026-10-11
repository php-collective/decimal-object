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

The first argument is the number of decimal places, `0` by default. The result always has exactly that scale, padded with zeros if needed.

The default mode rounds half away from zero, like PHP's `round()`. It works on the decimal digits and never passes through a float.

## Rounding modes

| Constant | Direction | `12.375` | `-12.375` |
| --- | --- | --- | --- |
| `Decimal::ROUND_HALF_UP` | nearest, half away from zero, at the given scale | `12.38` at scale 2 | `-12.38` at scale 2 |
| `Decimal::ROUND_FLOOR` | whole number towards negative infinity | `12` | `-13` |
| `Decimal::ROUND_CEIL` | whole number towards positive infinity | `13` | `-12` |

```php
echo $value->round(0, Decimal::ROUND_FLOOR); // 12
echo $value->round(0, Decimal::ROUND_CEIL);  // 13
```

::: warning Limits of the directed modes
`ROUND_FLOOR` and `ROUND_CEIL` always round to a whole number. A scale passed along with them does not move the rounding position.

Any other mode rounds half up. That includes PHP's `PHP_ROUND_HALF_EVEN`, `PHP_ROUND_HALF_ODD` and `PHP_ROUND_HALF_DOWN`, which are not supported. The next major version will apply the scale to all modes and reject unsupported ones.
:::

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

## Negative scale

Rounding to tens or hundreds is not supported. `round()` and `truncate()` throw an `InvalidArgumentException` for a scale below zero.
