# API reference

Class `PhpCollective\DecimalObject\Decimal`, implementing `JsonSerializable` and `Stringable`.

`$value` arguments accept a `Decimal`, a numeric string, an integer or a float unless noted. Methods that return a `Decimal` return a new instance of the called class.

## Creation

| Method | Description |
| --- | --- |
| `new Decimal($value, ?int $scale = null, bool $strict = false)` | Creates a decimal. Also accepts any `Stringable` with a numeric string. |
| `Decimal::create($value, ?int $scale = null, bool $strict = false): static` | Same as the constructor, for chaining. |
| `Decimal::tryCreate($value, ?int $scale = null): ?static` | Returns `null` for `null` and for input that is not numeric. |
| `Decimal::createFromFloat(float $value): static` | Keeps all digits of the float, independent of the `precision` ini setting. |
| `Decimal::zero(): static`, `Decimal::one(): static` | The values `0` and `1`. |

See [Creating decimals](../guide/creating).

## Arithmetic

| Method | Description | Default scale |
| --- | --- | --- |
| `add($value, ?int $scale = null): static` | Sum | larger of both |
| `subtract($value, ?int $scale = null): static` | Difference | larger of both |
| `multiply($value, ?int $scale = null): static` | Product | sum of both |
| `divide($value, int $scale, ?int $roundMode = null): static` | Quotient, cut off at `$scale`, or rounded when a mode is given | required |
| `divideWithRemainder($value): array` | Whole number quotient and remainder | 0, and larger of both |
| `mod($value, ?int $scale = null): static` | Remainder. `$value` is a `Decimal`, string or integer. | scale of the value |
| `pow($exponent, ?int $scale = null): static` | Power. `$exponent` is a whole number as `Decimal`, string or integer. | scale of the value |
| `sqrt(?int $scale = null): static` | Square root | scale of the value |
| `shift(int $places): static` | Moves the decimal point, multiplying by a power of ten | follows the digits |
| `absolute(): static` | Value without sign | unchanged |
| `negate(): static` | Value with the opposite sign | unchanged |

| Static method | Description |
| --- | --- |
| `Decimal::sum(iterable $values): static` | Sum, `0` for an empty list |
| `Decimal::average(iterable $values, int $scale): static` | Mean, cut off at `$scale` |
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
| `round(int $scale = 0, int $roundMode = Decimal::ROUND_HALF_UP): static` | Rounds to `$scale` decimal places, or to a whole number with `ROUND_FLOOR` and `ROUND_CEIL`. A negative scale rounds to tens, hundreds and so on. |
| `withScale(int $scale, int $roundMode = Decimal::ROUND_HALF_UP): static` | Pads or rounds to exactly `$scale` decimal places, in any mode. |
| `roundToStep($step, int $roundMode = Decimal::ROUND_HALF_UP): static` | Rounds to the nearest multiple of `$step`. |
| `floor(): static` | Nearest integer towards negative infinity. |
| `ceil(): static` | Nearest integer towards positive infinity. |
| `truncate(int $scale = 0): static` | Cuts off digits beyond `$scale`. |
| `trim(): static` | Removes trailing zeros after the decimal point. |

| Constant | Value |
| --- | --- |
| `Decimal::ROUND_HALF_UP` | `1`, same as `PHP_ROUND_HALF_UP` |
| `Decimal::ROUND_CEIL` | `7` |
| `Decimal::ROUND_FLOOR` | `8` |
| `Decimal::ROUND_HALF_DOWN` | `9` |
| `Decimal::ROUND_HALF_EVEN` | `10` |
| `Decimal::ROUND_HALF_ODD` | `11` |
| `Decimal::ROUND_DOWN` | `12`, towards zero |
| `Decimal::ROUND_UP` | `13`, away from zero |
| `Decimal::MAX_EXPONENT` | `10000`, limit for scientific notation input |

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
| `isBigDecimal(): bool` | `toFloat()` would refuse the value |
| `sign(): int` | `-1`, `0` or `1` |
| `scale(): int` | Number of decimal places |

## Conversion

| Method | Description |
| --- | --- |
| `toString(): string` | Exact decimal string, also used for `(string)` casts |
| `toInt(): int` | Integer without decimals. Throws `TypeError` when out of range. |
| `toFloat(): float` | Float approximation. Throws `TypeError` for big decimals. |
| `toScientific(): string` | Scientific notation such as `-2.2345e1` |
| `jsonSerialize(): string` | Same as `toString()` |

See [Conversion](../guide/conversion).

## Exceptions

| Situation | Exception |
| --- | --- |
| Input is not numeric, or a float is `INF` or `NAN` | `InvalidArgumentException` |
| Exponent beyond `Decimal::MAX_EXPONENT` | `InvalidArgumentException` |
| Negative scale in the constructor, `withScale()`, `truncate()` or `round()` with `ROUND_FLOOR` / `ROUND_CEIL` | `InvalidArgumentException` |
| Unknown mode in `withScale()` | `InvalidArgumentException` |
| Empty list in `average()`, `min()`, `max()`; invalid ratios in `allocate()`; `clamp()` with `$min` above `$max` | `InvalidArgumentException` |
| Strict mode and the input has more decimals than the scale | `InvalidArgumentException` |
| `divide()`, `divideWithRemainder()` or `mod()` by zero | `DivisionByZeroError` |
| Unknown mode in `divide()` or `roundToStep()`; negative scale in `divide()` with a mode; `roundToStep()` with a step of zero or below | `InvalidArgumentException` |
| `toInt()` or `toFloat()` on a value that does not fit | `TypeError` |
| `sqrt()` of a negative value, `pow()` with a fractional exponent, negative scale in an arithmetic method | `ValueError` from bcmath |

## Deprecated

| Method | Use instead |
| --- | --- |
| `greatherThanOrEquals()` | `greaterThanOrEquals()` |
