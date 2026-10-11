# Arithmetic

Every operand can be a `Decimal`, a numeric string or an integer. The result is always a new `Decimal`.

```php
use PhpCollective\DecimalObject\Decimal;

$a = Decimal::create('1.50');
```

## Exact by default

No operation drops a digit on its own. Addition, subtraction and multiplication return the exact result. Where the exact result cannot be written down, as in most divisions, you say how to round. If you do not, the call throws a `RoundingNecessaryException`.

> [!TIP] Changed in 2.0
> Results used to be cut off at the scale without notice. See [Upgrading](../reference/upgrading#from-1-x-to-2-0).

## Add and subtract

```php
echo $a->add('2.125');                      // 3.625
echo Decimal::create('5')->subtract('7.5'); // -2.5
```

The result has the larger scale of the two operands, which is always exact.

## Multiply

```php
echo Decimal::create('1.55')->multiply('2.00'); // 3.1000
```

The scales of both operands are added, which is always enough to hold the exact product.

## Asking for a scale

`add()`, `subtract()`, `multiply()` and `mod()` take a scale as second argument and a [rounding mode](./rounding#rounding-modes) as third:

```php
echo Decimal::create('1.55')->multiply('2.00', 2);                         // 3.10
echo Decimal::create('19.99')->multiply('0.19', 2, Decimal::ROUND_HALF_UP); // 3.80
```

The first line needs no mode because only zeros are dropped. The second would throw without one, since `3.7981` does not fit two decimals.

## Divide

`divide()` requires the scale, because a quotient can have endless decimals:

```php
echo Decimal::create('10')->divide('4', 2); // 2.50
```

That quotient fits. Most do not, and then a rounding mode is needed:

```php
Decimal::create('10')->divide('3', 4);
// RoundingNecessaryException: The result does not fit the scale.

echo Decimal::create('10')->divide('3', 4, Decimal::ROUND_HALF_UP); // 3.3333
echo Decimal::create('2')->divide('3', 2, Decimal::ROUND_HALF_UP);  // 0.67
echo Decimal::create('2')->divide('3', 2, Decimal::ROUND_DOWN);     // 0.66
```

The rounding is decided on the exact quotient, so ties are detected correctly at any scale. `ROUND_DOWN` gives the cut-off result of earlier versions.

Dividing by zero throws a `DivisionByZeroException`.

### Quotient and remainder

`divideWithRemainder()` returns the whole number quotient and what is left over:

```php
[$quotient, $remainder] = Decimal::create('17')->divideWithRemainder(5);
// 3 and 2

[$quotient, $remainder] = Decimal::create('10')->divideWithRemainder('0.3');
// 33 and 0.1
```

The quotient is cut off towards zero, so the remainder has the sign of the value, like `mod()`: `-7` by `2` gives `-3` and `-1`. Quotient times divisor plus remainder always equals the value.

## Modulo

```php
echo Decimal::create('7')->mod(2);      // 1
echo Decimal::create('-7')->mod(2);     // -1
echo Decimal::create('10')->mod('0.3'); // 0.1
```

The sign follows the left operand, as with PHP's `%`. The result has the larger scale of both operands. A zero operand throws a `DivisionByZeroException`.

## Power

```php
echo Decimal::create('8')->pow(2);   // 64
echo Decimal::create('1.5')->pow(2); // 2.25
echo Decimal::create('1.5')->pow(3); // 3.375
```

A positive exponent gives the exact result. Pass a scale and a mode to shorten it:

```php
echo Decimal::create('1.5')->pow(2, 1, Decimal::ROUND_HALF_UP); // 2.3
```

A negative exponent is a division. It uses the scale you pass, or the scale of the value, and needs a mode unless the result fits:

```php
echo Decimal::create('2')->pow(-1, 2);                         // 0.50
echo Decimal::create('3')->pow(-1, 4, Decimal::ROUND_HALF_UP); // 0.3333
```

The exponent must be a whole number between -10000 and 10000 (`Decimal::MAX_EXPONENT`). Anything else throws an `InvalidArgumentException`.

## Square root

```php
echo Decimal::create('64')->sqrt();   // 8
echo Decimal::create('2.25')->sqrt(); // 1.50
```

The result has the scale of the value unless you pass one. A root that does not fit needs a mode:

```php
echo Decimal::create('18')->sqrt(5, Decimal::ROUND_HALF_UP); // 4.24264
echo Decimal::create('18')->sqrt(3, Decimal::ROUND_HALF_UP); // 4.243
```

A negative value throws a `NegativeNumberException`.

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
echo Decimal::sum(['1.5', 2, Decimal::create('3.25')]);             // 6.75
echo Decimal::average(['1', '2', '3'], 2);                          // 2.00
echo Decimal::average(['1', '2', '4'], 2, Decimal::ROUND_HALF_UP);  // 2.33
echo Decimal::min(['2.5', '-1', 7]);                                // -1
echo Decimal::max(['2.5', '-1', 7]);                                // 7
```

All four take any iterable. `sum()` of nothing is `0`. `average()`, `min()` and `max()` throw an `InvalidArgumentException` for an empty list. `average()` is a division: it needs the scale, and a mode when the mean does not fit.

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

## Chaining

```php
$total = Decimal::create('100')
    ->subtract('12.50')
    ->multiply('1.19')
    ->round(2);

echo $total; // 104.13
```
