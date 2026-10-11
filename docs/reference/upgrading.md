# Upgrading

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

## Coming in 2.0

These corrections change results or output for ordinary input, so they wait for the next major version. The current behavior is documented on the linked pages.

| Call | 1.x | 2.0 |
| --- | --- | --- |
| `new Decimal('1.5', 0)` | `1.5`, see [scale of 0](../guide/creating#setting-a-scale) | `1` |
| `round(0, PHP_ROUND_HALF_EVEN)` | rounds half up, see [rounding modes](../guide/rounding#rounding-modes) | `InvalidArgumentException` |
| `Decimal::create('1.25')->round(1, Decimal::ROUND_FLOOR)` | `1` | `1.2` |
| `Decimal::create('10')->mod('0.3')` | `0`, see [modulo](../guide/arithmetic#modulo) | `0.1` |
| `new Decimal('1.0e-5')` | scale 5 | scale 6 |
| `Decimal::create('5')->toScientific()` | `5.e0` | `5e0` |
