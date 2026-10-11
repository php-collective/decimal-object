# API reference

Class `PhpCollective\DecimalObject\Decimal`, implementing `JsonSerializable` and `Stringable`.

`$value` arguments accept a `Decimal`, a numeric string, an integer or a float unless noted. Methods that return a `Decimal` return a new instance of the called class.

## Creation

| Method | Description |
| --- | --- |
| `new Decimal($value, ?int $scale = null, bool $strict = false)` | Creates a decimal. Also accepts any `Stringable` with a numeric string. |
| `Decimal::create($value, ?int $scale = null, bool $strict = false): static` | Same as the constructor, for chaining. |

See [Creating decimals](../guide/creating).

## Arithmetic

| Method | Description | Default scale |
| --- | --- | --- |
| `add($value, ?int $scale = null): static` | Sum | larger of both |
| `subtract($value, ?int $scale = null): static` | Difference | larger of both |
| `multiply($value, ?int $scale = null): static` | Product | sum of both |
| `divide($value, int $scale): static` | Quotient, cut off at `$scale` | required |
| `mod($value, ?int $scale = null): static` | Remainder. `$value` is a `Decimal`, string or integer. | scale of the value |
| `pow($exponent, ?int $scale = null): static` | Power. `$exponent` is a whole number as `Decimal`, string or integer. | scale of the value |
| `sqrt(?int $scale = null): static` | Square root | scale of the value |
| `absolute(): static` | Value without sign | unchanged |
| `negate(): static` | Value with the opposite sign | unchanged |

See [Arithmetic](../guide/arithmetic).

## Rounding

| Method | Description |
| --- | --- |
| `round(int $scale = 0, int $roundMode = Decimal::ROUND_HALF_UP): static` | Rounds half up to `$scale` decimal places, or to a whole number with `ROUND_FLOOR` and `ROUND_CEIL`. |
| `floor(): static` | Nearest integer towards negative infinity. |
| `ceil(): static` | Nearest integer towards positive infinity. |
| `truncate(int $scale = 0): static` | Cuts off digits beyond `$scale`. |
| `trim(): static` | Removes trailing zeros after the decimal point. |

| Constant | Value |
| --- | --- |
| `Decimal::ROUND_HALF_UP` | `1`, same as `PHP_ROUND_HALF_UP` |
| `Decimal::ROUND_CEIL` | `7` |
| `Decimal::ROUND_FLOOR` | `8` |

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

See [Comparison and checks](../guide/comparison).

## Checks

| Method | Description |
| --- | --- |
| `isZero(): bool` | Value is zero at any scale |
| `isPositive(): bool` | Greater than zero |
| `isNegative(): bool` | Less than zero |
| `isInteger(): bool` | No decimals other than zeros |
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
| Negative scale in the constructor, `round()` or `truncate()` | `InvalidArgumentException` |
| Strict mode and the input has more decimals than the scale | `InvalidArgumentException` |
| `divide()` or `mod()` by zero | `DivisionByZeroError` |
| `toInt()` or `toFloat()` on a value that does not fit | `TypeError` |
| `sqrt()` of a negative value, `pow()` with a fractional exponent, negative scale in an arithmetic method | `ValueError` from bcmath |

## Deprecated

| Method | Use instead |
| --- | --- |
| `greatherThanOrEquals()` | `greaterThanOrEquals()` |
