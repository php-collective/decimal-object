<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\DecimalObject;

use DivisionByZeroError;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;
use TypeError;

class Decimal implements JsonSerializable, Stringable
{
    /**
     * @var string
     */
    public const EXP_MARK = 'e';

    /**
     * @var string
     */
    public const RADIX_MARK = '.';

    public const ROUND_HALF_UP = PHP_ROUND_HALF_UP;

    /**
     * @var int
     */
    public const ROUND_CEIL = 7;

    /**
     * @var int
     */
    public const ROUND_FLOOR = 8;

    /**
     * @var int
     */
    public const ROUND_HALF_DOWN = 9;

    /**
     * @var int
     */
    public const ROUND_HALF_EVEN = 10;

    /**
     * @var int
     */
    public const ROUND_HALF_ODD = 11;

    /**
     * Towards zero.
     *
     * @var int
     */
    public const ROUND_DOWN = 12;

    /**
     * Away from zero.
     *
     * @var int
     */
    public const ROUND_UP = 13;

    /**
     * Largest exponent accepted in scientific notation, in either direction.
     *
     * `1e1000000000` would otherwise expand to a string of a billion digits.
     *
     * @var int
     */
    public const MAX_EXPONENT = 10000;

    /**
     * Integral part of this decimal number.
     *
     * Value before the separator. Cannot be negative.
     */
    protected string $integralPart = '';

    /**
     * Fractional part of this decimal number.
     *
     * Value after the separator (decimals) as string. Must be numbers only.
     */
    protected string $fractionalPart = '';

    protected bool $negative = false;

    /**
     * Decimal places to be applied to results
     *
     * decimal(10,6) => 6
     */
    protected int $scale = 0;

    /**
     * @param object|string|float|int $value
     * @param int|null $scale Decimal places in the result. Omit to enable auto-detection.
     * @param bool $strict If scale should be strictly checked to avoid accidental precision loss.
     */
    public function __construct(object|string|float|int $value, ?int $scale = null, bool $strict = false)
    {
        $value = $this->parseValue($value);
        $value = $this->normalizeValue($value);

        $this->setValue($value, $scale);
        $this->setScale($scale, $strict);
        $this->normalizeZero();
    }

    /**
     * @return int
     */
    public function scale(): int
    {
        return $this->scale;
    }

    /**
     * @param object|string|float|int $value
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function parseValue(object|string|float|int $value): string
    {
        if (!(is_scalar($value) || $value instanceof Stringable)) {
            throw new InvalidArgumentException('Invalid value');
        }

        if ($value instanceof Stringable) {
            $value = (string)$value;
        }

        if (is_string($value) && !is_numeric(trim($value))) {
            throw new InvalidArgumentException('Invalid non numeric value');
        }

        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new InvalidArgumentException('Invalid non finite value');
            }

            // PHP renders large and small floats as `1.0E+25`, that zero is not a significant digit.
            return str_replace('.0E', 'E', (string)$value);
        }

        return (string)$value;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function normalizeValue(string $value): string
    {
        $value = trim($value);
        $sign = str_starts_with($value, '-') ? '-' : '';
        $value = ltrim(ltrim($value, '+-'), '0');
        if ($value === '' || !ctype_digit($value[0])) {
            $value = '0' . $value;
        }

        return $sign . $value;
    }

    /**
     * Returns a Decimal instance from the given value.
     *
     * If the value is already a Decimal instance, then (since immutable) return it unmodified.
     * Otherwise, create a new Decimal instance from the given value and return
     * it.
     *
     * @param object|string|float|int $value
     * @param int|null $scale Decimal places in the result. Omit to enable auto-detection.
     * @param bool $strict If scale should be strictly checked to avoid accidental precision loss.
     *
     * @return static
     */
    public static function create(object|string|float|int $value, ?int $scale = null, bool $strict = false): static
    {
        if ($scale === null && $value instanceof static) {
            return clone $value;
        }

        return new static($value, $scale, $strict);
    }

    /**
     * Like create(), but returns null for null and for values that are not numeric.
     *
     * @param object|string|float|int|null $value
     * @param int|null $scale Decimal places in the result. Omit to enable auto-detection.
     *
     * @return static|null
     */
    public static function tryCreate(object|string|float|int|null $value, ?int $scale = null): ?static
    {
        if ($value === null) {
            return null;
        }

        try {
            return static::create($value, $scale);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Creates a decimal from a float with all the digits needed to get the same float back.
     *
     * Passing a float to create() goes through PHP's string cast and its `precision`
     * ini setting, which drops digits beyond the 14th by default.
     *
     * @param float $value
     *
     * @throws \InvalidArgumentException If the float is INF or NAN.
     *
     * @return static
     */
    public static function createFromFloat(float $value): static
    {
        if (!is_finite($value)) {
            throw new InvalidArgumentException('Invalid non finite value');
        }

        $string = '0';
        foreach ([15, 16, 17] as $digits) {
            $string = sprintf('%.' . $digits . 'H', $value);
            if ((float)$string === $value) {
                break;
            }
        }

        return new static(str_replace('.0E', 'E', $string));
    }

    /**
     * @return static
     */
    public static function zero(): static
    {
        return new static('0');
    }

    /**
     * @return static
     */
    public static function one(): static
    {
        return new static('1');
    }

    /**
     * @param iterable<object|string|float|int> $values
     *
     * @return static
     */
    public static function sum(iterable $values): static
    {
        $sum = new static('0');
        foreach ($values as $value) {
            $sum = $sum->add(static::create($value));
        }

        return $sum;
    }

    /**
     * @param iterable<object|string|float|int> $values
     * @param int $scale Decimal places in the result
     *
     * @throws \InvalidArgumentException If there are no values.
     *
     * @return static
     */
    public static function average(iterable $values, int $scale): static
    {
        $sum = new static('0');
        $count = 0;
        foreach ($values as $value) {
            $sum = $sum->add(static::create($value));
            $count++;
        }
        if ($count === 0) {
            throw new InvalidArgumentException('Cannot average an empty list.');
        }

        return $sum->divide($count, $scale);
    }

    /**
     * @param iterable<object|string|float|int> $values
     *
     * @return static
     */
    public static function min(iterable $values): static
    {
        return static::extreme($values, -1);
    }

    /**
     * @param iterable<object|string|float|int> $values
     *
     * @return static
     */
    public static function max(iterable $values): static
    {
        return static::extreme($values, 1);
    }

    /**
     * @param iterable<object|string|float|int> $values
     * @param int $direction -1 for the smallest, 1 for the largest value
     *
     * @throws \InvalidArgumentException
     *
     * @return static
     */
    protected static function extreme(iterable $values, int $direction): static
    {
        $result = null;
        foreach ($values as $value) {
            $decimal = static::create($value);
            if ($result === null || $decimal->compareTo($result) === $direction) {
                $result = $decimal;
            }
        }
        if ($result === null) {
            throw new InvalidArgumentException('Cannot pick from an empty list.');
        }

        return $result;
    }

    /**
     * Equality
     *
     * This method is equivalent to the `==` operator.
     *
     * @param self|string|float|int $value
     *
     * @return bool TRUE if this decimal is considered equal to the given value.
     *  Equal decimal values tie-break on precision.
     */
    public function equals(self|string|float|int $value): bool
    {
        return $this->compareTo($value) === 0;
    }

    /**
     * @param self|string|float|int $value
     *
     * @return bool
     */
    public function greaterThan(self|string|float|int $value): bool
    {
        return $this->compareTo($value) > 0;
    }

    /**
     * @param self|string|float|int $value
     *
     * @return bool
     */
    public function lessThan(self|string|float|int $value): bool
    {
        return $this->compareTo($value) < 0;
    }

    /**
     * @param self|string|float|int $value
     *
     * @return bool
     */
    public function greaterThanOrEquals(self|string|float|int $value): bool
    {
        return $this->compareTo($value) >= 0;
    }

    /**
     * @deprecated Use {@link greaterThanOrEquals()} instead.
     *
     * @param self|string|float|int $value
     *
     * @return bool
     */
    public function greatherThanOrEquals(self|string|float|int $value): bool
    {
        return $this->greaterThanOrEquals($value);
    }

    /**
     * @param self|string|float|int $value
     *
     * @return bool
     */
    public function lessThanOrEquals(self|string|float|int $value): bool
    {
        return $this->compareTo($value) <= 0;
    }

    /**
     * Compare this Decimal with a value.
     *
     * Returns
     * - `-1` if the instance is less than the $value,
     * - `0` if the instance is equal to $value, or
     * - `1` if the instance is greater than $value.
     *
     * @param self|string|float|int $value
     *
     * @return int
     */
    public function compareTo(self|string|float|int $value): int
    {
        $decimal = static::create($value);
        $scale = max($this->scale(), $decimal->scale());

        return bccomp((string)$this, (string)$decimal, $scale);
    }

    /**
     * Add $value to this Decimal and return the sum as a new Decimal.
     *
     * @param self|string|float|int $value
     * @param int|null $scale Decimal places in the result. Omit to enable auto-detection.
     *
     * @return static
     */
    public function add(self|string|float|int $value, ?int $scale = null): static
    {
        $decimal = static::create($value);
        $scale = $this->resultScale($this, $decimal, $scale);

        return new static(bcadd((string)$this, (string)$decimal, $scale));
    }

    /**
     * Return an appropriate scale for an arithmetic operation on two Decimals.
     *
     * If $scale is specified and is a valid positive integer, return it.
     * Otherwise, return the higher of the scales of the operands.
     *
     * @param self $a
     * @param self $b
     * @param int|null $scale
     *
     * @return int
     */
    protected function resultScale(self $a, self $b, ?int $scale = null): int
    {
        return $scale ?? max($a->scale(), $b->scale());
    }

    /**
     * Subtract $value from this Decimal and return the difference as a new
     * Decimal.
     *
     * @param self|string|float|int $value
     * @param int|null $scale Decimal places in the result. Omit to enable auto-detection.
     *
     * @return static
     */
    public function subtract(self|string|float|int $value, ?int $scale = null): static
    {
        $decimal = static::create($value);
        $scale = $this->resultScale($this, $decimal, $scale);

        return new static(bcsub((string)$this, (string)$decimal, $scale));
    }

    /**
     * Trims trailing zeroes.
     *
     * @return static
     */
    public function trim(): static
    {
        return $this->copy($this->integralPart, $this->trimDecimals($this->fractionalPart));
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function trimDecimals(string $value): string
    {
        return rtrim($value, '0') ?: '';
    }

    /**
     * Signum
     *
     * @return int 0 if zero, -1 if negative, or 1 if positive.
     */
    public function sign(): int
    {
        if ($this->isZero()) {
            return 0;
        }

        return $this->negative ? -1 : 1;
    }

    /**
     * Returns the absolute (positive) value of this decimal.
     *
     * @return static
     */
    public function absolute(): static
    {
        return $this->copy($this->integralPart, $this->fractionalPart, false);
    }

    /**
     * Returns the negation (negative to positive and vice versa).
     *
     * @return static
     */
    public function negate(): static
    {
        return $this->copy(null, null, !$this->isNegative());
    }

    /**
     * @return bool
     */
    public function isInteger(): bool
    {
        return trim($this->fractionalPart, '0') === '';
    }

    /**
     * Returns if truly zero.
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return $this->integralPart === '0' && $this->isInteger();
    }

    /**
     * Returns if truly negative (not zero).
     *
     * @return bool
     */
    public function isNegative(): bool
    {
        return $this->negative;
    }

    /**
     * Returns if truly positive (not zero).
     *
     * @return bool
     */
    public function isPositive(): bool
    {
        return !$this->negative && !$this->isZero();
    }

    /**
     * Returns if a whole number divisible by two. Always false for values with decimals.
     *
     * @return bool
     */
    public function isEven(): bool
    {
        return $this->isInteger() && (int)substr($this->integralPart, -1) % 2 === 0;
    }

    /**
     * Returns if a whole number not divisible by two. Always false for values with decimals.
     *
     * @return bool
     */
    public function isOdd(): bool
    {
        return $this->isInteger() && (int)substr($this->integralPart, -1) % 2 === 1;
    }

    /**
     * @param self|string|float|int $min
     * @param self|string|float|int $max
     * @param bool $inclusive If the bounds themselves count as in between.
     *
     * @return bool
     */
    public function between(self|string|float|int $min, self|string|float|int $max, bool $inclusive = true): bool
    {
        if ($inclusive) {
            return $this->compareTo($min) >= 0 && $this->compareTo($max) <= 0;
        }

        return $this->compareTo($min) > 0 && $this->compareTo($max) < 0;
    }

    /**
     * Returns this value limited to the given range.
     *
     * @param self|string|float|int $min
     * @param self|string|float|int $max
     *
     * @throws \InvalidArgumentException If $min is greater than $max.
     *
     * @return static
     */
    public function clamp(self|string|float|int $min, self|string|float|int $max): static
    {
        $min = static::create($min);
        $max = static::create($max);
        if ($min->greaterThan($max)) {
            throw new InvalidArgumentException('Min must not be greater than max.');
        }
        if ($this->lessThan($min)) {
            return $min;
        }
        if ($this->greaterThan($max)) {
            return $max;
        }

        return clone $this;
    }

    /**
     * Multiply this Decimal by $value and return the product as a new Decimal.
     *
     * @param self|string|float|int $value
     * @param int|null $scale Decimal places in the result. Omit to enable auto-detection.
     *
     * @return static
     */
    public function multiply(self|string|float|int $value, ?int $scale = null): static
    {
        $decimal = static::create($value);
        if ($scale === null) {
            $scale = $this->scale() + $decimal->scale();
        }

        return new static(bcmul((string)$this, (string)$decimal, $scale));
    }

    /**
     * Divide this Decimal by $value and return the quotient as a new Decimal.
     *
     * @param self|string|float|int $value
     * @param int $scale Decimal places in the result
     * @param int|null $roundMode One of the ROUND_* constants. Omit to cut off after $scale places.
     *
     * @throws \DivisionByZeroError if $value is zero.
     * @throws \InvalidArgumentException
     *
     * @return static
     */
    public function divide(self|string|float|int $value, int $scale, ?int $roundMode = null): static
    {
        $decimal = static::create($value);
        if ($decimal->isZero()) {
            throw new DivisionByZeroError('Cannot divide by zero. Only Chuck Norris can!');
        }
        if ($roundMode === null) {
            return new static(bcdiv((string)$this, (string)$decimal, $scale));
        }
        if ($scale < 0) {
            throw new InvalidArgumentException('Scale must be >= 0.');
        }

        return new static($this->roundedQuotient($decimal, $scale, $roundMode), $scale);
    }

    /**
     * Whole number quotient and what is left over: quotient * $value + remainder equals this value.
     *
     * The quotient is cut off towards zero, so the remainder has the sign of this value, like mod().
     *
     * @param self|string|float|int $value
     *
     * @throws \DivisionByZeroError if $value is zero.
     *
     * @return array{0: static, 1: static} Quotient and remainder
     */
    public function divideWithRemainder(self|string|float|int $value): array
    {
        $decimal = static::create($value);
        if ($decimal->isZero()) {
            throw new DivisionByZeroError('Cannot divide by zero. Only Chuck Norris can!');
        }

        $scale = max($this->fractionalLength(), $decimal->fractionalLength());
        $quotient = bcdiv((string)$this, (string)$decimal, 0);
        $remainder = bcsub((string)$this, bcmul($quotient, (string)$decimal, $scale), $scale);

        return [new static($quotient), new static($remainder)];
    }

    /**
     * Rounds to the nearest multiple of $step, for example 0.05 for cash amounts.
     *
     * @param self|string|float|int $step Greater than zero
     * @param int $roundMode One of the ROUND_* constants
     *
     * @throws \InvalidArgumentException
     *
     * @return static
     */
    public function roundToStep(self|string|float|int $step, int $roundMode = self::ROUND_HALF_UP): static
    {
        $step = static::create($step);
        if (!$step->isPositive()) {
            throw new InvalidArgumentException('Step must be greater than zero.');
        }

        $steps = $this->roundedQuotient($step, 0, $roundMode);
        $scale = $step->fractionalLength();

        return new static(bcmul($steps, (string)$step, $scale), $scale);
    }

    /**
     * @param self $divisor Not zero
     * @param int $scale
     * @param int $roundMode
     *
     * @throws \InvalidArgumentException
     *
     * @return string This value divided by $divisor, rounded to $scale decimal places.
     */
    protected function roundedQuotient(self $divisor, int $scale, int $roundMode): string
    {
        if (!in_array($roundMode, static::roundModes(), true)) {
            throw new InvalidArgumentException('Unsupported round mode `' . $roundMode . '`.');
        }

        // Work on whole numbers so the remainder tells exactly whether and how to round.
        $places = max($this->fractionalLength(), $divisor->fractionalLength());
        $numerator = $this->shift($places)->integralPart . str_repeat('0', $scale);
        $denominator = $divisor->shift($places)->integralPart;

        $quotient = bcdiv($numerator, $denominator, 0);
        $remainder = bcsub($numerator, bcmul($quotient, $denominator, 0), 0);

        $exact = $remainder === '0';
        $half = $exact ? -1 : bccomp(bcmul($remainder, '2', 0), $denominator, 0);
        $odd = (int)substr($quotient, -1) % 2 === 1;
        $negative = $this->negative !== $divisor->negative;

        if (static::roundsAwayFromZero($roundMode, $negative, $exact, $half, $odd)) {
            $quotient = bcadd($quotient, '1', 0);
        }

        return (string)(new static(($negative ? '-' : '') . $quotient))->shift(-$scale);
    }

    /**
     * @return int Number of decimals actually stored, which a scale of 0 does not limit in 1.x.
     */
    protected function fractionalLength(): int
    {
        return strlen($this->fractionalPart);
    }

    /**
     * This method is equivalent to the ** operator.
     *
     * @param self|string|float|int $exponent
     * @param int|null $scale Decimal places in the result. Omit to enable auto-detection.
     *
     * @return static
     */
    public function pow(self|string|float|int $exponent, ?int $scale = null): static
    {
        if ($scale === null) {
            $scale = $this->scale();
        }

        return new static(bcpow((string)$this, (string)static::create($exponent), $scale));
    }

    /**
     * Returns the square root of this decimal, with the same scale as this decimal.
     *
     * @param int|null $scale Decimal places in the result. Omit to enable auto-detection.
     *
     * @return static
     */
    public function sqrt(?int $scale = null): static
    {
        if ($scale === null) {
            $scale = $this->scale();
        }

        return new static(bcsqrt((string)$this, $scale));
    }

    /**
     * This method is equivalent to the % operator.
     *
     * @param self|string|float|int $value
     * @param int|null $scale Decimal places in the result. Omit to enable auto-detection.
     *
     * @return static
     */
    public function mod(self|string|float|int $value, ?int $scale = null): static
    {
        if ($scale === null) {
            $scale = $this->scale();
        }

        return new static(bcmod((string)$this, (string)static::create($value), $scale));
    }

    /**
     * ROUND_FLOOR and ROUND_CEIL always round to a whole number, use withScale() to round
     * in those directions at a scale. A mode that is not a ROUND_* constant rounds half up.
     *
     * @param int $scale Decimal places in the result. Negative values round to tens, hundreds and so on.
     * @param int $roundMode One of the ROUND_* constants
     *
     * @throws \InvalidArgumentException
     *
     * @return static
     */
    public function round(int $scale = 0, int $roundMode = self::ROUND_HALF_UP): static
    {
        if (in_array($roundMode, [static::ROUND_FLOOR, static::ROUND_CEIL], true)) {
            if ($scale < 0) {
                throw new InvalidArgumentException('Scale must be >= 0.');
            }

            return new static($this->roundToScale(0, $roundMode), $scale);
        }

        if (!in_array($roundMode, static::roundModes(), true)) {
            $roundMode = static::ROUND_HALF_UP;
        }

        return new static($this->roundToScale($scale, $roundMode), max($scale, 0));
    }

    /**
     * Returns this value with exactly the given number of decimal places.
     *
     * Missing places are filled with zeros, surplus places are rounded. Unlike round(),
     * every mode including ROUND_FLOOR and ROUND_CEIL is applied at the scale.
     *
     * @param int $scale Decimal places in the result
     * @param int $roundMode One of the ROUND_* constants
     *
     * @throws \InvalidArgumentException
     *
     * @return static
     */
    public function withScale(int $scale, int $roundMode = self::ROUND_HALF_UP): static
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('Scale must be >= 0.');
        }
        if (!in_array($roundMode, static::roundModes(), true)) {
            throw new InvalidArgumentException('Unsupported round mode `' . $roundMode . '`.');
        }
        if ($scale >= strlen($this->fractionalPart)) {
            return $this->copy(null, str_pad($this->fractionalPart, $scale, '0'));
        }

        return new static($this->roundToScale($scale, $roundMode), $scale);
    }

    /**
     * Moves the decimal point, which multiplies by a power of ten without losing digits.
     *
     * @param int $places Positive to the right (larger value), negative to the left.
     *
     * @return static
     */
    public function shift(int $places): static
    {
        $digits = $this->integralPart . $this->fractionalPart;
        $point = strlen($this->integralPart) + $places;
        if ($point <= 0) {
            $digits = str_repeat('0', 1 - $point) . $digits;
            $point = 1;
        }
        $digits = str_pad($digits, $point, '0');

        $value = substr($digits, 0, $point);
        if (strlen($digits) > $point) {
            $value .= static::RADIX_MARK . substr($digits, $point);
        }

        return new static(($this->negative ? '-' : '') . $value);
    }

    /**
     * @return array<int>
     */
    protected static function roundModes(): array
    {
        return [
            static::ROUND_HALF_UP,
            static::ROUND_HALF_DOWN,
            static::ROUND_HALF_EVEN,
            static::ROUND_HALF_ODD,
            static::ROUND_UP,
            static::ROUND_DOWN,
            static::ROUND_CEIL,
            static::ROUND_FLOOR,
        ];
    }

    /**
     * @param int $scale
     * @param int $roundMode
     *
     * @return string
     */
    protected function roundToScale(int $scale, int $roundMode): string
    {
        $shifted = $this->shift($scale);
        $integralPart = $shifted->integralPart;
        $fractionalPart = $shifted->fractionalPart;

        $exact = trim($fractionalPart, '0') === '';
        $half = $exact ? -1 : bccomp('0.' . $fractionalPart, '0.5', strlen($fractionalPart));
        $odd = (int)substr($integralPart, -1) % 2 === 1;

        if (static::roundsAwayFromZero($roundMode, $this->negative, $exact, $half, $odd)) {
            $integralPart = bcadd($integralPart, '1', 0);
        }

        return (string)(new static(($this->negative ? '-' : '') . $integralPart))->shift(-$scale);
    }

    /**
     * @param int $roundMode
     * @param bool $negative
     * @param bool $exact If there is nothing to round.
     * @param int $half -1, 0 or 1 for a rest below, at or above the half way point
     * @param bool $odd If the last kept digit is odd.
     *
     * @return bool If the last kept digit has to move one step away from zero.
     */
    protected static function roundsAwayFromZero(int $roundMode, bool $negative, bool $exact, int $half, bool $odd): bool
    {
        return match ($roundMode) {
            static::ROUND_FLOOR => !$exact && $negative,
            static::ROUND_CEIL => !$exact && !$negative,
            static::ROUND_UP => !$exact,
            static::ROUND_DOWN => false,
            static::ROUND_HALF_DOWN => $half > 0,
            static::ROUND_HALF_EVEN => $half > 0 || ($half === 0 && $odd),
            static::ROUND_HALF_ODD => $half > 0 || ($half === 0 && !$odd),
            default => $half >= 0,
        };
    }

    /**
     * Splits this value by the given ratios without losing or inventing anything.
     *
     * The shares add up to this value exactly. What cannot be divided evenly at the scale
     * of this value goes to the first shares, one smallest unit each.
     *
     * @param array<object|string|float|int> $ratios Non negative weights, keys are preserved.
     *
     * @throws \InvalidArgumentException
     *
     * @return array<static>
     */
    public function allocate(array $ratios): array
    {
        $weights = [];
        $ratioScale = 0;
        foreach ($ratios as $key => $ratio) {
            $weight = static::create($ratio);
            if ($weight->isNegative()) {
                throw new InvalidArgumentException('Ratios must not be negative.');
            }
            $weights[$key] = (string)$weight;
            $ratioScale = max($ratioScale, $weight->scale());
        }

        $total = '0';
        foreach ($weights as $weight) {
            $total = bcadd($total, $weight, $ratioScale);
        }
        if (bccomp($total, '0', $ratioScale) !== 1) {
            throw new InvalidArgumentException('Ratios must add up to more than zero.');
        }

        $scale = max($this->scale(), $this->fractionalLength());
        $units = $this->absolute()->shift($scale)->integralPart;

        $shares = [];
        $remainder = $units;
        foreach ($weights as $key => $weight) {
            $shares[$key] = bcdiv(bcmul($units, $weight, $ratioScale), $total, 0);
            $remainder = bcsub($remainder, $shares[$key], 0);
        }
        foreach ($weights as $key => $weight) {
            if ($remainder === '0') {
                break;
            }
            if (bccomp($weight, '0', $ratioScale) === 1) {
                $shares[$key] = bcadd($shares[$key], '1', 0);
                $remainder = bcsub($remainder, '1', 0);
            }
        }

        $sign = $this->negative ? '-' : '';
        foreach ($shares as $key => $share) {
            $shares[$key] = (new static($sign . $share))->shift(-$scale);
        }

        return $shares;
    }

    /**
     * Splits this value into equal parts, see allocate().
     *
     * @param int $parts
     *
     * @throws \InvalidArgumentException
     *
     * @return array<static>
     */
    public function split(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Parts must be >= 1.');
        }

        return $this->allocate(array_fill(0, $parts, 1));
    }

    /**
     * The closest integer towards negative infinity.
     *
     * @return static
     */
    public function floor(): static
    {
        return $this->round(0, static::ROUND_FLOOR);
    }

    /**
     * The closest integer towards positive infinity.
     *
     * @return static
     */
    public function ceil(): static
    {
        return $this->round(0, static::ROUND_CEIL);
    }

    /**
     * The result of discarding all digits behind the defined scale.
     *
     * @param int $scale Decimal places in the result
     *
     * @throws \InvalidArgumentException
     *
     * @return static
     */
    public function truncate(int $scale = 0): static
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('Scale must be >= 0.');
        }

        $decimalPart = substr($this->fractionalPart, 0, $scale);

        return $this->copy($this->integralPart, $decimalPart);
    }

    /**
     * Return some approximation of this Decimal as a PHP native float.
     *
     * Due to the nature of binary floating-point, some valid values of Decimal
     * will not have any finite representation as a float, and some valid
     * values of Decimal will be out of the range handled by floats.
     *
     * @throws \TypeError
     *
     * @return float
     */
    public function toFloat(): float
    {
        if ($this->isBigDecimal()) {
            throw new TypeError('Cannot cast Big Decimal to Float');
        }

        return (float)$this->toString();
    }

    /**
     * Returns the decimal as int. Does not round.
     *
     * This method is equivalent to a cast to int.
     *
     * @throws \TypeError
     *
     * @return int
     */
    public function toInt(): int
    {
        if ($this->isBigInteger()) {
            throw new TypeError('Cannot cast Big Integer to Integer');
        }

        return (int)$this->toString();
    }

    /**
     * @return bool
     */
    public function isBigInteger(): bool
    {
        $value = ($this->negative ? '-' : '') . $this->integralPart;

        return bccomp($value, (string)PHP_INT_MAX) === 1 || bccomp($value, (string)PHP_INT_MIN) === -1;
    }

    /**
     * @return bool
     */
    public function isBigDecimal(): bool
    {
        return $this->isBigInteger() || bccomp($this->fractionalPart ?: '0', (string)PHP_INT_MAX) === 1;
    }

    /**
     * Returns scientific notation.
     *
     * {x.y}e{z} with 0 < x < 10
     *
     * This does not lose precision/scale info.
     * If you want the output without the significant digits added,
     * use trim() beforehand.
     *
     * @return string
     */
    public function toScientific(): string
    {
        if ($this->isZero()) {
            return $this->toString() . static::EXP_MARK . '0';
        }

        if ($this->integralPart !== '0') {
            $exponent = strlen($this->integralPart) - 1;
            $digits = $this->integralPart . $this->fractionalPart;
            if ($this->fractionalPart === '') {
                $digits = rtrim($digits, '0');
            }
        } else {
            $digits = ltrim($this->fractionalPart, '0');
            $exponent = strlen($digits) - strlen($this->fractionalPart) - 1;
        }

        // The radix mark stays even without decimals (`5.e0`) in 1.x.
        $value = $digits[0] . static::RADIX_MARK . substr($digits, 1);

        if ($this->negative) {
            $value = '-' . $value;
        }

        return $value . static::EXP_MARK . $exponent;
    }

    /**
     * String representation.
     *
     * This method is equivalent to a cast to string.
     *
     * This method should not be used as a canonical representation of this
     * decimal, because values can be represented in more than one way. However,
     * this method does guarantee that a decimal instantiated by its output with
     * the same scale will be exactly equal to this decimal.
     *
     * @return string the value of this decimal represented exactly.
     */
    public function toString(): string
    {
        if ($this->fractionalPart !== '') {
            return ($this->negative ? '-' : '') . $this->integralPart . '.' . $this->fractionalPart;
        }

        return ($this->negative ? '-' : '') . $this->integralPart;
    }

    /**
     * Return a basic string representation of this Decimal.
     *
     * The output of this method is guaranteed to yield exactly the same value
     * if fed back into the Decimal constructor.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Get the printable version of this object
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'value' => $this->toString(),
            'scale' => $this->scale,
        ];
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * @param string|null $integerPart
     * @param string|null $decimalPart
     * @param bool|null $negative
     *
     * @return static
     */
    protected function copy(?string $integerPart = null, ?string $decimalPart = null, ?bool $negative = null): static
    {
        $clone = clone $this;
        if ($integerPart !== null) {
            $clone->integralPart = $integerPart;
        }
        if ($decimalPart !== null) {
            $clone->fractionalPart = $decimalPart;
            $clone->setScale(null, false);
        }
        if ($negative !== null) {
            $clone->negative = $negative;
        }
        $clone->normalizeZero();

        return $clone;
    }

    /**
     * Separates int and decimal parts and adds them to the state.
     *
     * - Removes leading 0 on int part
     * - '0.00001' can also come in as '1.0E-5'
     *
     * @param string $value
     * @param int|null $scale
     *
     * @return void
     */
    protected function setValue(string $value, ?int $scale): void
    {
        if (preg_match('#(.+)e(.+)#i', $value) === 1) {
            $this->fromScientific($value, $scale);

            return;
        }

        if (str_contains($value, '.')) {
            $this->fromFloat($value);

            return;
        }

        $this->fromInt($value);
    }

    /**
     * @param string $value
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function fromInt(string $value): void
    {
        preg_match('/^(-)?([^.]+)$/', $value, $matches);
        if ($matches === []) {
            throw new InvalidArgumentException('Invalid integer number');
        }

        $this->negative = $matches[1] === '-';
        $this->integralPart = $matches[2];
        $this->fractionalPart = '';
    }

    /**
     * @param string $value
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function fromFloat(string $value): void
    {
        preg_match('/^(-)?(.*)\.(.*)$/', $value, $matches);
        if ($matches === []) {
            throw new InvalidArgumentException('Invalid float number');
        }

        $this->negative = $matches[1] === '-';
        $this->integralPart = $matches[2];
        $this->fractionalPart = $matches[3];
    }

    /**
     * @param string $value
     * @param int|null $scale
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function fromScientific(string $value, ?int $scale): void
    {
        $pattern = '/^(-?)(\d+)(?:' . preg_quote(static::RADIX_MARK, '/') . '(\d*))?' . static::EXP_MARK . '([+-]?\d+)$/i';
        if (preg_match($pattern, $value, $matches) !== 1) {
            throw new InvalidArgumentException('Invalid scientific value/notation: ' . $value);
        }

        $this->negative = $matches[1] === '-';
        $integralPart = $matches[2];
        // A mantissa of `1.0` is read as `1` in 1.x.
        $fractionalPart = $matches[3] === '0' ? '' : $matches[3];
        $exp = (int)$matches[4];
        if (abs($exp) > static::MAX_EXPONENT) {
            throw new InvalidArgumentException('Exponent must be between -' . static::MAX_EXPONENT . ' and ' . static::MAX_EXPONENT . '.');
        }

        if ($exp < 0) {
            $integralPart = str_pad($integralPart, -$exp + 1, '0', STR_PAD_LEFT);
            $fractionalPart = substr($integralPart, $exp) . $fractionalPart;
            $integralPart = substr($integralPart, 0, $exp);
        } else {
            $integralPart .= substr(str_pad($fractionalPart, $exp, '0'), 0, $exp);
            $fractionalPart = substr($fractionalPart, $exp);
        }

        $this->integralPart = ltrim($integralPart, '0') ?: '0';
        $this->fractionalPart = $fractionalPart;

        if ($scale !== null) {
            // 1.x pads positive exponents to the scale minus the integral digits.
            $length = $exp < 0 ? $scale : $scale - strlen($this->integralPart);
            $this->fractionalPart = str_pad($this->fractionalPart, $length, '0');
        }
    }

    /**
     * @param int|null $scale
     * @param bool $strict
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function setScale(?int $scale, bool $strict): void
    {
        if ($scale !== null && $scale < 0) {
            throw new InvalidArgumentException('Scale must be >= 0.');
        }

        // A scale of 0 is treated like no scale in 1.x and does not cut off decimals.
        $calculatedScale = strlen($this->fractionalPart);
        if ($scale && $calculatedScale > $scale) {
            if ($strict) {
                throw new InvalidArgumentException('Loss of precision detected. Detected scale `' . $calculatedScale . '` > `' . $scale . '` as defined.');
            }

            $this->fractionalPart = substr($this->fractionalPart, 0, $scale);
        }

        $this->scale = $scale ?? $calculatedScale;
    }

    /**
     * Zero has no sign, `-0.00` would otherwise report as negative.
     *
     * @return void
     */
    protected function normalizeZero(): void
    {
        if ($this->negative && $this->isZero()) {
            $this->negative = false;
        }
    }
}
