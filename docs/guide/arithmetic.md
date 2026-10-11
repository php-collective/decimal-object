# Arithmetic

Every operand can be a `Decimal`, a numeric string, an integer or a float. The result is always a new `Decimal`.

```php
use PhpCollective\DecimalObject\Decimal;

$a = Decimal::create('1.50');
```

## Add and subtract

```php
echo $a->add('2.125');                    // 3.625
echo Decimal::create('5')->subtract('7.5'); // -2.5
```

The result has the larger scale of the two operands.

## Multiply

```php
echo Decimal::create('1.55')->multiply('2.00');    // 3.1000
echo Decimal::create('1.55')->multiply('2.00', 2); // 3.10
```

Without a scale argument the scales of both operands are added, which is always enough to hold the exact product.

## Divide

`divide()` requires the scale, because a quotient can have endless decimals:

```php
echo Decimal::create('10')->divide('3', 4); // 3.3333
echo Decimal::create('2')->divide('3', 2);  // 0.66
```

The result is cut off at the scale, not rounded. Pass a rounding mode as third argument to round it:

```php
echo Decimal::create('2')->divide('3', 2, Decimal::ROUND_HALF_UP);   // 0.67
echo Decimal::create('1')->divide('8', 2, Decimal::ROUND_HALF_EVEN); // 0.12
```

The rounding is decided on the exact quotient. All [rounding modes](./rounding#rounding-modes) work here at the scale, including floor and ceil.

> [!TIP] Added in 1.4
> The rounding mode argument. Without it `divide()` cuts off as before.

### Quotient and remainder

`divideWithRemainder()` returns the whole number quotient and what is left over:

```php
[$quotient, $remainder] = Decimal::create('17')->divideWithRemainder(5);
// 3 and 2

[$quotient, $remainder] = Decimal::create('10')->divideWithRemainder('0.3');
// 33 and 0.1
```

The quotient is cut off towards zero, so the remainder has the sign of the value, like `mod()`: `-7` by `2` gives `-3` and `-1`. Quotient times divisor plus remainder always equals the value.

> [!TIP] Added in 1.4

Dividing by zero throws a `DivisionByZeroError`.

## Modulo

```php
echo Decimal::create('7')->mod(2);         // 1
echo Decimal::create('-7')->mod(2);        // -1
echo Decimal::create('10')->mod('0.3', 1); // 0.1
```

The sign follows the left operand, as with PHP's `%`. A zero operand throws a `DivisionByZeroError`.

::: warning Fractional divisors
The result has the scale of the value `mod()` is called on. `Decimal::create('10')->mod('0.3')` therefore returns `0`, because the remainder `0.1` does not fit a scale of 0. Pass the scale as second argument when the divisor has more decimals.
:::

## Power and square root

```php
echo Decimal::create('8')->pow(2);       // 64
echo Decimal::create('1.5')->pow(2);     // 2.2
echo Decimal::create('1.5')->pow(2, 2);  // 2.25
echo Decimal::create('2')->pow(-1, 2);   // 0.50

echo Decimal::create('18')->sqrt();      // 4
echo Decimal::create('18')->sqrt(5);     // 4.24264
```

Both default to the scale of the value they are called on and cut off further digits, so pass a scale when the result needs more decimals than the input has.

The exponent of `pow()` must be a whole number, and `sqrt()` of a negative value is not defined. bcmath reports both with a `ValueError`.

## Sign

```php
echo Decimal::create('-23.44')->absolute(); // 23.44
echo Decimal::create('23.44')->negate();    // -23.44
```

## Moving the decimal point

`shift()` multiplies by a power of ten without touching the digits. Use it to convert between units, such as an amount and its cents:

```php
echo Decimal::create('19.99')->shift(2);  // 1999
echo Decimal::create('1999')->shift(-2);  // 19.99
echo Decimal::create('12.34')->shift(-3); // 0.01234
```

## Several values at once

```php
echo Decimal::sum(['1.5', 2, Decimal::create('3.25')]); // 6.75
echo Decimal::average(['1', '2', '4'], 2);              // 2.33
echo Decimal::min(['2.5', '-1', 7]);                    // -1
echo Decimal::max(['2.5', '-1', 7]);                    // 7
```

All four take any iterable. `sum()` of nothing is `0`. `average()`, `min()` and `max()` throw an `InvalidArgumentException` for an empty list. `average()` needs the scale for the same reason `divide()` does, and cuts off there.

## Splitting an amount

Dividing an amount and rounding each share rarely adds up to the original. `split()` and `allocate()` hand out every smallest unit:

```php
$shares = Decimal::create('100.00')->split(3);
// 33.34, 33.33, 33.33

$shares = Decimal::create('100.00')->allocate([70, 30]);
// 70.00, 30.00

$shares = Decimal::create('10.00')->allocate(['rent' => 2, 'food' => 1]);
// ['rent' => 6.67, 'food' => 3.33]
```

The shares always sum to the original value. They have its scale, which also sets the smallest unit: `10` splits into `4, 3, 3`. What is left after the even division goes to the first shares, one unit each. Keys of the ratio array are kept, and a ratio of `0` receives nothing.

Ratios can be integers or decimals such as `'0.7'` and `'0.3'`. Negative ratios, an empty list and ratios that sum to zero throw an `InvalidArgumentException`.

> [!TIP] Added in 1.4
> `shift()`, `sum()`, `average()`, `min()`, `max()`, `split()` and `allocate()`.

## Chaining

```php
$total = Decimal::create('100')
    ->subtract('12.50')
    ->multiply('1.19')
    ->round(2);

echo $total; // 104.13
```
