# Upgrading

## From 1.x to 2.0

2.0 has one rule behind most of its changes: the library never drops a digit unless you tell it how. Calls that used to shorten a value without notice now either return the exact result or throw.

### Floats are rejected

A float passed to the constructor, `create()` or any arithmetic method throws an `InvalidArgumentException`. 1.x accepted floats and cut them to 14 significant digits.

- Pass strings: `Decimal::create('19.90')`.
- Where a float is unavoidable, use `Decimal::createFromFloat($float)` and round the result to the scale you expect.

### Division, roots and powers need a rounding mode

| Call | 1.x | 2.0 |
| --- | --- | --- |
| `Decimal::create('10')->divide('3', 4)` | `3.3333` | `RoundingNecessaryException` |
| `Decimal::create('10')->divide('4', 2)` | `2.50` | `2.50` |
| `Decimal::create('18')->sqrt()` | `4` | `RoundingNecessaryException` |
| `Decimal::create('64')->sqrt()` | `8` | `8` |
| `Decimal::create('1.5')->pow(2)` | `2.2` | `2.25` |
| `Decimal::average(['1', '2', '4'], 2)` | `2.33` | `RoundingNecessaryException` |

Add `Decimal::ROUND_DOWN` as last argument to keep the 1.x result, or pick the mode you meant, for example `Decimal::ROUND_HALF_UP`. Results that fit the scale need no change.

`pow()` with a positive exponent now returns the exact result instead of cutting it to the scale of the base.

### A scale argument no longer cuts off

| Call | 1.x | 2.0 |
| --- | --- | --- |
| `Decimal::create('0.129', 2)` | `0.12` | `RoundingNecessaryException` |
| `Decimal::create('0.120', 2)` | `0.12` | `0.12` |
| `Decimal::create('1.5', 0)` | `1.5` | `RoundingNecessaryException` |
| `Decimal::create('19.99')->multiply('0.19', 2)` | `3.79` | `RoundingNecessaryException` |
| `Decimal::create('1.55')->multiply('2.00', 2)` | `3.10` | `3.10` |

`add()`, `subtract()`, `multiply()` and `mod()` take the rounding mode as third argument. For a value, round explicitly: `Decimal::create('0.129')->withScale(2, Decimal::ROUND_DOWN)`.

The `$strict` argument of the constructor and of `create()` is removed. Its check is now always on.

### The string shows the scale

| Call | 1.x | 2.0 |
| --- | --- | --- |
| `(string)Decimal::create('0.1', 4)` | `0.1` | `0.1000` |
| `(string)Decimal::create('1e3', 2)` | `1000` | `1000.00` |

Stored and serialized values gain trailing zeros where a larger scale was declared. Call `trim()` if you need the short form.

### Rounding

| Call | 1.x | 2.0 |
| --- | --- | --- |
| `round(0, PHP_ROUND_HALF_EVEN)` | rounds half up | `InvalidArgumentException` |
| `Decimal::create('1.25')->round(1, Decimal::ROUND_FLOOR)` | `1` | `1.2` |
| `Decimal::create('15')->round(-1, Decimal::ROUND_FLOOR)` | `InvalidArgumentException` | `10` |

Replace `PHP_ROUND_*` constants with the `Decimal::ROUND_*` ones, or with the native `RoundingMode` enum on PHP 8.4+. `round()` with `ROUND_FLOOR` or `ROUND_CEIL` and a scale above zero used to return a whole number; call `floor()` or `ceil()` if that is what you want.

### Modulo

| Call | 1.x | 2.0 |
| --- | --- | --- |
| `Decimal::create('10')->mod('0.3')` | `0` | `0.1` |
| `Decimal::create('10')->mod('3.00')` | `1` | `1.00` |

The result takes the larger scale of both operands.

### Scientific notation

A zero written in a string mantissa counts towards the scale: `'1.0e-5'` has a scale of 6. `toScientific()` no longer prints a decimal point without decimals: `5e0` where 1.x printed `5.e0`.

### Exceptions

The library throws its own exceptions from `PhpCollective\DecimalObject\Exception`, all implementing `DecimalException`. Most extend the type thrown before, so existing `catch` blocks keep working. These do not:

| Situation | 1.x | 2.0 |
| --- | --- | --- |
| `toInt()` out of range | `TypeError` | `OverflowException` |
| `sqrt()` of a negative value | `ValueError` | `NegativeNumberException` |
| `pow()` with a fractional exponent | `ValueError` | `InvalidArgumentException` |
| Digits lost against a scale in strict mode | `InvalidArgumentException` | `RoundingNecessaryException` |

See the [exception table](./api#exceptions).

### Conversion

`toInt()` no longer drops decimals on its own:

| Call | 1.x | 2.0 |
| --- | --- | --- |
| `Decimal::create('23.74')->toInt()` | `23` | `RoundingNecessaryException` |
| `Decimal::create('23.00')->toInt()` | `23` | `23` |
| `Decimal::create('23.74')->toInt(Decimal::ROUND_DOWN)` | `23` | `23` |

1.4 ignores the argument, so calls can be changed before the upgrade.

`toFloat()` returns the nearest float for any value in the float range. It used to throw for values with long fractions.

### Objects as input

Only `Stringable` objects are accepted as value, which every class with a `__toString()` method is. Any other object is a `TypeError`; it was an `InvalidArgumentException` before.

`Decimal::create()` returns the `Decimal` it was given when no scale is passed. 1.x returned a copy, which only shows in identity comparisons with `===`.

### Removed

- `greatherThanOrEquals()`, the misspelled alias. Use `greaterThanOrEquals()`.
- `isBigDecimal()`. `toFloat()` no longer depends on it.
- `isBigInteger()`. Use `fitsInt()`, which answers the opposite question.
- The `$strict` argument.

### Subclasses

`setScale()` lost its `$strict` parameter and `resultScale()` is gone. Serialization uses `__serialize()` and stores the decimal string; data written by 1.x is still read.

## From 1.3 to 1.4

PHP 8.2 is now required. Composer keeps PHP 8.1 projects on 1.3.

### New

- Rounding modes `ROUND_HALF_DOWN`, `ROUND_HALF_EVEN`, `ROUND_HALF_ODD`, `ROUND_UP` and `ROUND_DOWN`, see [Rounding](../guide/rounding#rounding-modes)
- `withScale()` to pad or round to a fixed scale, and `round()` with a negative scale
- `divide()` takes a rounding mode as third argument, `divideWithRemainder()` returns quotient and remainder
- `roundToStep()` for multiples such as 0.05
- `Decimal::createFromFloat()`, `zero()` and `one()`
- `Decimal::sum()`, `average()`, `min()` and `max()`
- `split()` and `allocate()` to divide an amount without a remainder getting lost
- `shift()`, `between()`, `clamp()`, `isEven()`, `isOdd()` and `Decimal::tryCreate()`

### Exponent limit

Scientific notation is limited to exponents between -10000 and 10000. Larger ones throw an `InvalidArgumentException`.

### Fixes

The release fixes wrong results and does not change the result of a call that was correct before. Check the following if your code worked around one of the bugs.

#### Parsing

- Scientific notation with more than one digit before the decimal point and a negative exponent was parsed wrong, for example `'12.5e-1'` as `0.125`. Stored values that came from such input need correcting.
- `'1e+5'` and large floats such as `1e25` are accepted. They threw before.
- Leading zeros are removed in all forms: `'0010'` is `10`, `'-007'` is `-7`.
- `INF` and `NAN` throw an `InvalidArgumentException`. They were stored as the strings `INF` and `NAN`.
- A negative scale throws an `InvalidArgumentException`.

#### Negative zero

Zero has no sign. `'-0.0'` becomes `0.0`, `isNegative()` is false for it, and `negate()`, `truncate()` and `trim()` no longer return `-0`.

#### Rounding

`floor()` and `ceil()` no longer move whole numbers written with decimals. `Decimal::create('-1.0')->floor()` returned `-2`, and `Decimal::create('1.0')->ceil()` returned `2`.


#### Conversion

- `toScientific()` returned `.e-1` for zero, lost digits beyond the float precision for large integers, and returned `1.250e2` for `120.50`.
- `toInt()` accepts `PHP_INT_MIN`.

#### Operands

`mod()` and `pow()` accept operands in scientific notation.

## 1.4.1

`mod()` and `pow()` now accept float operands. Subclasses overriding these methods must include `float` in their operand type to keep their signatures compatible.

## 1.4.2

Comparisons use every stored decimal. `Decimal::create('1.9', 0)->compareTo(1)` returned `0` and now returns `1`. In 2.0 that value cannot be created without a rounding mode.
