# API reference

Class `PhpCollective\DecimalObject\Decimal`, implementing `JsonSerializable` and `Stringable`.

`$value` arguments accept a `Decimal`, a numeric string or an integer. A float throws, see [Floats](../guide/creating#floats). Methods that return a `Decimal` return a new instance of the called class.

`$roundMode` arguments accept a `Decimal::ROUND_*` constant or, on PHP 8.4+, a native `RoundingMode`.

## Creation

| Method | Description |
| --- | --- |
| `new Decimal($value, ?int $scale = null)` | Creates a decimal. Also accepts any `Stringable` with a numeric string. |
| `Decimal::create($value, ?int $scale = null): static` | Same as the constructor, for chaining. |
| `Decimal::tryCreate($value, ?int $scale = null): ?static` | Returns `null` for `null` and for input that is not numeric. |
| `Decimal::createFromFloat(float $value): static` | The only way to pass a float. Keeps all its digits. |
| `Decimal::zero(): static`, `Decimal::one(): static` | The values `0` and `1`. |

See [Creating decimals](../guide/creating).

## Arithmetic

Results are exact. A `$scale` smaller than the exact result needs a `$roundMode`, otherwise a `RoundingNecessaryException` is thrown.

| Method | Description | Scale without `$scale` |
| --- | --- | --- |
| `add($value, ?int $scale = null, $roundMode = ROUND_UNNECESSARY): static` | Sum | larger of both |
| `subtract($value, ?int $scale = null, $roundMode = ROUND_UNNECESSARY): static` | Difference | larger of both |
| `multiply($value, ?int $scale = null, $roundMode = ROUND_UNNECESSARY): static` | Product | sum of both |
| `divide($value, int $scale, $roundMode = ROUND_UNNECESSARY): static` | Quotient | required |
| `divideWithRemainder($value): array` | Whole number quotient and remainder | 0, and larger of both |
| `mod($value, ?int $scale = null, $roundMode = ROUND_UNNECESSARY): static` | Remainder | larger of both |
| `pow($exponent, ?int $scale = null, $roundMode = ROUND_UNNECESSARY): static` | Power, `$exponent` a whole number | exact for a positive exponent, scale of the value for a negative one |
| `sqrt(?int $scale = null, $roundMode = ROUND_UNNECESSARY): static` | Square root | scale of the value |
| `shift(int $places): static` | Moves the decimal point, multiplying by a power of ten | follows the digits |
| `absolute(): static` | Value without sign | unchanged |
| `negate(): static` | Value with the opposite sign | unchanged |

| Static method | Description |
| --- | --- |
| `Decimal::sum(iterable $values): static` | Sum, `0` for an empty list |
| `Decimal::average(iterable $values, int $scale, $roundMode = ROUND_UNNECESSARY): static` | Mean at `$scale` |
| `Decimal::min(iterable $values): static` | Smallest value |
| `Decimal::max(iterable $values): static` | Largest value |

| Method | Description |
| --- | --- |
| `allocate(array $ratios): array` | Shares by ratio that sum to the value exactly, keys preserved |
| `split(int $parts): array` | Equal shares that sum to the value exactly |

See [Arithmetic](../guide/arithmetic).

## Rounding

| Method | Description |
| --- | --- |
| `round(int $scale = 0, $roundMode = ROUND_HALF_UP): static` | Rounds to `$scale` decimal places. A negative scale rounds to tens, hundreds and so on. |
| `withScale(int $scale, $roundMode = ROUND_HALF_UP): static` | Pads or rounds to exactly `$scale` decimal places. |
| `roundToStep($step, $roundMode = ROUND_HALF_UP): static` | Rounds to the nearest multiple of `$step`. |
| `floor(): static` | Nearest integer towards negative infinity. |
| `ceil(): static` | Nearest integer towards positive infinity. |
| `truncate(int $scale = 0): static` | Cuts off digits beyond `$scale`. |
| `trim(): static` | Removes trailing zeros after the decimal point. |

| Constant | Value | Native `RoundingMode` |
| --- | --- | --- |
| `Decimal::ROUND_HALF_UP` | `1` | `HalfAwayFromZero` |
| `Decimal::ROUND_CEIL` | `7` | `PositiveInfinity` |
| `Decimal::ROUND_FLOOR` | `8` | `NegativeInfinity` |
| `Decimal::ROUND_HALF_DOWN` | `9` | `HalfTowardsZero` |
| `Decimal::ROUND_HALF_EVEN` | `10` | `HalfEven` |
| `Decimal::ROUND_HALF_ODD` | `11` | `HalfOdd` |
| `Decimal::ROUND_DOWN` | `12` | `TowardsZero` |
| `Decimal::ROUND_UP` | `13` | `AwayFromZero` |
| `Decimal::ROUND_UNNECESSARY` | `14` | none |

`Decimal::MAX_EXPONENT` is `10000`, the limit for scientific notation input.

See [Rounding](../guide/rounding).

## Comparison

| Method | Description |
| --- | --- |
| `compareTo($value): int` | `-1`, `0` or `1` |
| `equals($value): bool` | Same value, scale ignored |
| `greaterThan($value): bool` | |
| `greaterThanOrEquals($value): bool` | |
| `lessThan($value): bool` | |
| `lessThanOrEquals($value): bool` | |
| `between($min, $max, bool $inclusive = true): bool` | Within the range |
| `clamp($min, $max): static` | The value, or the nearest bound when outside the range |

See [Comparison and checks](../guide/comparison).

## Checks

| Method | Description |
| --- | --- |
| `isZero(): bool` | Value is zero at any scale |
| `isPositive(): bool` | Greater than zero |
| `isNegative(): bool` | Less than zero |
| `isInteger(): bool` | No decimals other than zeros |
| `isEven(): bool` | Whole number divisible by two |
| `isOdd(): bool` | Whole number not divisible by two |
| `isBigInteger(): bool` | Integral part does not fit a PHP integer |
| `sign(): int` | `-1`, `0` or `1` |
| `scale(): int` | Number of decimal places |

## Conversion

| Method | Description |
| --- | --- |
| `toString(): string` | Exact decimal string, also used for `(string)` casts |
| `toInt(): int` | Integer without decimals. Throws `OverflowException` when out of range. |
| `toFloat(): float` | Nearest float. Throws `OverflowException` beyond the float range. |
| `toScientific(): string` | Scientific notation such as `-2.2345e1` |
| `jsonSerialize(): string` | Same as `toString()` |

See [Conversion](../guide/conversion).

## Exceptions

All exceptions are in `PhpCollective\DecimalObject\Exception` and implement `DecimalException`, so one `catch` covers the library. Each also extends the SPL type listed, which keeps existing `catch` blocks for those working.

| Exception | Extends | Thrown when |
| --- | --- | --- |
| `InvalidNumberException` | `\InvalidArgumentException` | Input is not a number, or its exponent is beyond `MAX_EXPONENT` |
| `InvalidArgumentException` | `\InvalidArgumentException` | A float is passed; negative scale; unknown rounding mode; exponent in `pow()` that is fractional or beyond `MAX_EXPONENT`; empty list in `average()`, `min()`, `max()`; invalid ratios in `allocate()`; `clamp()` with `$min` above `$max`; step of zero or below |
| `RoundingNecessaryException` | `\RuntimeException` | A result or input does not fit the scale and no rounding mode was given |
| `DivisionByZeroException` | `\DivisionByZeroError` | `divide()`, `divideWithRemainder()` or `mod()` by zero; zero raised to a negative power |
| `NegativeNumberException` | `\DomainException` | `sqrt()` of a negative value |
| `OverflowException` | `\OverflowException` | `toInt()` or `toFloat()` on a value that does not fit |

```php
use PhpCollective\DecimalObject\Exception\DecimalException;

try {
    $rate = Decimal::create($input)->divide($total, 4);
} catch (DecimalException $e) {
    // bad input, division by zero, or a quotient that needs rounding
}
```
