#  Decimal VO documentation

Decimal value object for PHP.

## Basic information

The value objects are immutable, always assign them when running modifications on them.

## Usage

You can create the value object from
- string
- integer
- float
- object that implements Stringable (and numeric `toString()` method)

```php
use PhpCollective\DecimalObject\Decimal;

$value = '1.234';
$decimalObject = Decimal::create($value);

echo 'Value: ' . $decimalObject;
```

It will auto-cast to string where necessary, you can force it using `(string)$decimalObject`.

### Operations

Transformation:
- `sign()`: Returns int `0` if zero, `-1` if negative, or `1` if positive.
- `toString()`: Default casting mechanism (this method is equivalent to a cast to string).
- `toFloat()`: Returns some approximation of this Decimal as a PHP native float.
- `toInt()`: Returns integer value (this method is equivalent to a cast to integer).

These return a new object:
- `absolute()`: Returns the absolute (positive) value of this decimal.
- `negate()`: Returns the negation (positive if negative and vice versa).
- `trim()`: Remove trailing zeros after the comma (same value, but different semantic meaning in term of precision/scale).

There is only one static method and acts as a convenience wrapper to create an object:
- `create()`: Internally does `new Decimal($value)`, allows for easier chaining without need of `()` wrapping.
  Use this if your input can already be the `Decimal` object as it will then just be re-used. Constructor creation
  builds a new object.

### Checks

Boolean checks:
- `isZero()`: If exactly `0`.
- `isNegative()`: If < `0`.
- `isPositive()`: If > `0` (zero itself is not included).

### Comparison

- `compareTo()`: Compare this Decimal to another value.
- `equals()`: This method is equivalent to the `==` operator.
- `greaterThan()`: Returns true if the Decimal is greater than given value.
- `lessThan()`: Returns true if the Decimal is smaller than given value.
- `greaterThanOrEquals()`: Returns true if the Decimal is greater than or equal to the given value.
- `lessThanOrEquals()`: Returns true if the Decimal is greater than or equal to the given value.

### Math and Calculation
You can use
- `add()`
- `subtract()`
- `multiply()`
- `divide()`
- `round()`: Round using different modes.
- `truncate()`: Truncate after places of decimal.
- `mod()`
- `pow()`
- `sqrt()`

```php
$decimalOne = Decimal::create('1.1');
$decimalTwo = Decimal::create('2.2');

$decimalAdded = $decimalOne->add($decimalTwo); // Now '3.3'
```

Note that due to immutability `$decimalOne` is not modified here. The re-assignment is necessary for the operation to persist.


## Contributing

You can contribute as pull request directly:
- Target `master` branch for: bugfixes, improvements that are BC
- Target `next` branch (next major version) for: Any BC breaking behavior, cleanup like removing deprecations or alike
