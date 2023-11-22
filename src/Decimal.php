<?php

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
     * decimal(10,6) => 6
     */
    protected int $scale = 2;

    /**
     * @param static|string|float|int $value
     * @param int|null $scale
     */
    public function __construct(self|string|float|int $value, ?int $scale = null)
    {
        $value = $this->parseValue($value);
        $value = $this->normalizeValue($value);

        $this->setValue($value, $scale);
        $this->setScale($scale);
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

        if (is_string($value) && !is_numeric(trim($value))) {
            throw new InvalidArgumentException('Invalid non numeric value');
        }

        if (!is_string($value)) {
            $value = (string)$value;
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function normalizeValue(string $value): string
    {
        $value = trim($value);
        /** @var string $value */
        $value = preg_replace(
            [
                '/^^([\-]?)(\.)(.*)$/', // omitted leading zero
                '/^0+(.)(\..*)?$/', // multiple leading zeros
                '/^(\+(.*)|(-)(0))$/', // leading positive sign, tolerate minus zero too
            ],
            [
                '${1}0.${3}',
                '${1}${2}',
                '${4}${2}',
            ],
            $value,
        );

        return $value;
    }

    /**
     * Returns a Decimal instance from the given value.
     *
     * If the value is already a Decimal instance, then (since immutable) return it unmodified.
     * Otherwise, create a new Decimal instance from the given value and return
     * it.
     *
     * @param static|string|float|int $value
     * @param int|null $scale
     *
     * @return static
     */
    public static function create(self|string|float|int $value, ?int $scale = null): static
    {
        if ($scale === null && $value instanceof static) {
            return clone $value;
        }

        return new static($value, $scale);
    }

    /**
     * Equality
     *
     * This method is equivalent to the `==` operator.
     *
     * @param static|string|float|int $value
     *
     * @return bool TRUE if this decimal is considered equal to the given value.
     *  Equal decimal values tie-break on precision.
     */
    public function equals(self|string|float|int $value): bool
    {
        return $this->compareTo($value) === 0;
    }

    /**
     * @param static|string|float|int $value
     *
     * @return bool
     */
    public function greaterThan(self|string|float|int $value): bool
    {
        return $this->compareTo($value) > 0;
    }

    /**
     * @param static|string|float|int $value
     *
     * @return bool
     */
    public function lessThan(self|string|float|int $value): bool
    {
        return $this->compareTo($value) < 0;
    }

    /**
     * @param static|string|float|int $value
     *
     * @return bool
     */
    public function greaterThanOrEquals(self|string|float|int $value): bool
    {
        return ($this->compareTo($value) >= 0);
    }

    /**
     * @deprecated Use {@link greaterThanOrEquals()} instead.
     *
     * @param static|string|float|int $value
     *
     * @return bool
     */
    public function greatherThanOrEquals(self|string|float|int $value): bool
    {
        return $this->greaterThanOrEquals($value);
    }

    /**
     * @param static|string|float|int $value
     *
     * @return bool
     */
    public function lessThanOrEquals(self|string|float|int $value): bool
    {
        return ($this->compareTo($value) <= 0);
    }

    /**
     * Compare this Decimal with a value.
     *
     * Returns
     * - `-1` if the instance is less than the $value,
     * - `0` if the instance is equal to $value, or
     * - `1` if the instance is greater than $value.
     *
     * @param static|string|float|int $value
     *
     * @return int
     */
    public function compareTo(self|string|float|int $value): int
    {
        $decimal = static::create($value);
        $scale = max($this->scale(), $decimal->scale());

        return bccomp($this, $decimal, $scale);
    }

    /**
     * Add $value to this Decimal and return the sum as a new Decimal.
     *
     * @param static|string|float|int $value
     * @param int|null $scale
     *
     * @return static
     */
    public function add(self|string|float|int $value, ?int $scale = null): static
    {
        $decimal = static::create($value);
        $scale = $this->resultScale($this, $decimal, $scale);

        return new static(bcadd($this, $decimal, $scale));
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
     * @param static|string|float|int $value
     * @param int|null $scale
     *
     * @return static
     */
    public function subtract(self|string|float|int $value, ?int $scale = null): static
    {
        $decimal = static::create($value);
        $scale = $this->resultScale($this, $decimal, $scale);

        return new static(bcsub($this, $decimal, $scale));
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
     * Multiply this Decimal by $value and return the product as a new Decimal.
     *
     * @param static|string|float|int $value
     * @param int|null $scale
     *
     * @return static
     */
    public function multiply(self|string|int|float $value, ?int $scale = null): static
    {
        $decimal = static::create($value);
        if ($scale === null) {
            $scale = $this->scale() + $decimal->scale();
        }

        return new static(bcmul($this, $decimal, $scale));
    }

    /**
     * Divide this Decimal by $value and return the quotient as a new Decimal.
     *
     * @param static|string|float|int $value
     * @param int $scale
     *
     * @throws \DivisionByZeroError if $value is zero.
     *
     * @return static
     */
    public function divide(self|string|int|float $value, int $scale): static
    {
        $decimal = static::create($value);
        if ($decimal->isZero()) {
            throw new DivisionByZeroError('Cannot divide by zero. Only Chuck Norris can!');
        }

        return new static(bcdiv($this, $decimal, $scale));
    }

    /**
     * This method is equivalent to the ** operator.
     *
     * @param static|string|int $exponent
     * @param int|null $scale
     *
     * @return static
     */
    public function pow(self|string|int $exponent, ?int $scale = null): static
    {
        if ($scale === null) {
            $scale = $this->scale();
        }

        return new static(bcpow($this, (string)$exponent, $scale));
    }

    /**
     * Returns the square root of this decimal, with the same scale as this decimal.
     *
     * @param int|null $scale
     *
     * @return static
     */
    public function sqrt(?int $scale = null): static
    {
        if ($scale === null) {
            $scale = $this->scale();
        }

        return new static(bcsqrt($this, $scale));
    }

    /**
     * This method is equivalent to the % operator.
     *
     * @param static|string|int $value
     * @param int|null $scale
     *
     * @return static
     */
    public function mod(self|string|int $value, ?int $scale = null): static
    {
        if ($scale === null) {
            $scale = $this->scale();
        }

        return new static(bcmod($this, (string)$value, $scale));
    }

    /**
     * @param int $scale
     * @param int $roundMode
     *
     * @return static
     */
    public function round(int $scale = 0, int $roundMode = self::ROUND_HALF_UP): static
    {
        $exponent = $scale + 1;

        $e = bcpow('10', (string)$exponent);
        switch ($roundMode) {
            case static::ROUND_FLOOR:
                $v = bcdiv(bcadd(bcmul($this, $e, 0), $this->isNegative() ? '-9' : '0'), $e, 0);

                break;
            case static::ROUND_CEIL:
                $v = bcdiv(bcadd(bcmul($this, $e, 0), $this->isNegative() ? '0' : '9'), $e, 0);

                break;
            case static::ROUND_HALF_UP:
            default:
                $v = bcdiv(bcadd(bcmul($this, $e, 0), $this->isNegative() ? '-5' : '5'), $e, $scale);
        }

        return new static($v);
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
     * @param int $scale
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
        return bccomp($this->integralPart, (string)PHP_INT_MAX) === 1 || bccomp($this->integralPart, (string)PHP_INT_MIN) === -1;
    }

    /**
     * @return bool
     */
    public function isBigDecimal(): bool
    {
        return $this->isBigInteger() ||
            bccomp($this->fractionalPart, (string)PHP_INT_MAX) === 1 || bccomp($this->fractionalPart, (string)PHP_INT_MIN) === -1;
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
        if ($this->integralPart) {
            $exponent = 0;
            $integralPart = $this->integralPart;
            while ($integralPart >= 10) {
                $integralPart /= 10;
                $exponent++;
            }

            $value = (string)$integralPart;
            if (strpos($value, '.') === false) {
                $value .= '.';
            }
            $value .= $this->fractionalPart;
        } else {
            $exponent = -1;
            // 00002
            // 20000
            $fractionalPart = $this->fractionalPart;
            while (substr($fractionalPart, 0, 1) === '0') {
                $fractionalPart = substr($fractionalPart, 1);
                $exponent--;
            }

            $pos = abs($exponent) - 1;
            $value = substr($this->fractionalPart, $pos, 1) . '.' . substr($this->fractionalPart, $pos + 1);
        }

        if ($this->negative) {
            $value = '-' . $value;
        }

        return $value . 'e' . $exponent;
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
            $clone->setScale(null);
        }
        if ($negative !== null) {
            $clone->negative = $negative;
        }

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

        if (strpos($value, '.') !== false) {
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
        $pattern = '/^(-?)(\d+(?:' . static::RADIX_MARK . '\d*)?|' .
            '[' . static::RADIX_MARK . ']' . '\d+)' . static::EXP_MARK . '(-?\d*)?$/i';
        preg_match($pattern, $value, $matches);
        if (!$matches) {
            throw new InvalidArgumentException('Invalid scientific value/notation: ' . $value);
        }

        $this->negative = $matches[1] === '-';
        /** @var string $value */
        $value = preg_replace('/\b\.0$/', '', $matches[2]);
        $exp = (int)$matches[3];

        if ($exp < 0) {
            $this->integralPart = '0';
            /** @var string $value */
            $value = preg_replace('/^(\d+)(\.)?(\d+)$/', '${1}${3}', $value, 1);
            $this->fractionalPart = str_repeat('0', -$exp - 1) . $value;

            if ($scale !== null) {
                $this->fractionalPart = str_pad($this->fractionalPart, $scale, '0');
            }
        } else {
            $this->integralPart = bcmul($matches[2], bcpow('10', (string)$exp));

            $pos = strlen($this->integralPart);
            $pos = strlen($this->integralPart);
            if (strpos($value, '.') !== false) {
                $pos++;
            }
            $this->fractionalPart = rtrim(substr($value, $pos), '.');

            if ($scale !== null) {
                $this->fractionalPart = str_pad($this->fractionalPart, $scale - strlen($this->integralPart), '0');
            }
        }
    }

    /**
     * @param int|null $scale
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function setScale(?int $scale): void
    {
        $calculatedScale = strlen($this->fractionalPart);
        if ($scale && $calculatedScale > $scale) {
            throw new InvalidArgumentException('Loss of precision detected. Detected scale `' . $calculatedScale . '` > `' . $scale . '` as defined.');
        }

        $this->scale = $scale ?? $calculatedScale;
    }
}
