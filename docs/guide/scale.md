# Precision and scale

The scale is the number of digits after the decimal point. `12.375` has a scale of 3, `120` has a scale of 0.

```php
use PhpCollective\DecimalObject\Decimal;

echo Decimal::create('1.50')->scale(); // 2
```

The library is strict about it: a value keeps the scale it was created with, and trailing zeros count. `1.50` and `1.5` are equal in value but carry different scales.

## Scale of a result

| Operation | Default scale | Example |
| --- | --- | --- |
| `add()`, `subtract()` | larger scale of both operands | `1.50 + 2.125 = 3.625` |
| `multiply()` | sum of both scales | `1.55 * 2.00 = 3.1000` |
| `divide()` | none, the argument is required | `10 / 3` at scale 4 is `3.3333` |
| `divideWithRemainder()` | 0 for the quotient, larger scale of both for the remainder | `10 / 0.3` is `33` and `0.1` |
| `pow()`, `sqrt()`, `mod()` | scale of the value | `1.5 ** 2 = 2.2` |
| `round()`, `truncate()` | requested scale; truncation does not pad | `12.375` rounded to 2 is `12.38` |

`add()`, `subtract()`, `multiply()`, `divide()`, `pow()`, `sqrt()` and `mod()` accept a result scale. When the exact result has more digits than the scale allows, the rest is cut off without rounding. `divide()` rounds when you pass a mode as third argument.

## Repeated multiplication

Because `multiply()` adds scales, a loop grows the scale on every pass:

```php
$value = Decimal::create('1.5');

echo $value->multiply('1.5')->multiply('1.5'); // 3.375
```

Pass the scale you want, or round at the end of the calculation:

```php
echo Decimal::create('19.99')->multiply('0.19');           // 3.7981
echo Decimal::create('19.99')->multiply('0.19')->round(2); // 3.80
```

## Division loses information

A cut-off quotient does not multiply back to the original:

```php
echo Decimal::create('100')->divide('3', 2)->multiply(3); // 99.99
```

To split an amount without losing a cent, use [`split()` or `allocate()`](./arithmetic#splitting-an-amount).

## Setting a fixed scale

`withScale()` pads or rounds to an exact number of decimal places:

```php
echo Decimal::create('7')->withScale(2);     // 7.00
echo Decimal::create('1.255')->withScale(2); // 1.26
```

See [Rounding](./rounding#withscale) for the rounding modes it accepts.

## Removing trailing zeros

`trim()` drops trailing zeros and lowers the scale to match:

```php
$trimmed = Decimal::create('1.2300')->trim();

echo $trimmed;          // 1.23
echo $trimmed->scale(); // 2

echo Decimal::create('100.00')->trim(); // 100
```

Zeros before the decimal point belong to the value and stay.

## Large and small numbers

There is no upper limit other than memory:

```php
echo Decimal::create('99999999999999999999.99')->add('0.01');
// 100000000000000000000.00
```

`isBigInteger()` and `isBigDecimal()` tell you whether a value still fits PHP's native types. See [Conversion](./conversion).
