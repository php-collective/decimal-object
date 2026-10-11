# Alternatives

This library is one way to handle decimals in PHP. Depending on the project another one may fit better.

| Library | Notes |
| --- | --- |
| [php-decimal](https://php-decimal.github.io/) | A PHP extension with arithmetic operators, installed separately on each server. Works with precision (significant digits) where this library works with scale. |
| [brick/math](https://github.com/brick/math) | `BigDecimal`, `BigInteger` and `BigRational` with several calculation engines and more rounding modes. |
| [brick/money](https://github.com/brick/money), [moneyphp/money](https://github.com/moneyphp/money) | Money with currencies, allocation and formatting. Choose one of these when you need currency handling and not only a number. |

## Coming from the php-decimal extension

| php-decimal | Decimal Object |
| --- | --- |
| Operators `+`, `-`, `*`, `/` | Methods `add()`, `subtract()`, `multiply()`, `divide()` |
| `abs()` | `absolute()` |
| `signum()` | `sign()` |
| `sub()`, `mul()`, `div()` | `subtract()`, `multiply()`, `divide()` |
| `shift()`, `isEven()`, `isOdd()` | Same method names |
| `rem($value)` | `mod($value, $scale)`; set the result scale explicitly |
| `mod($value)` | Integer modulo in php-decimal; see the different [modulo behavior](../guide/arithmetic#modulo) here |
| `toFixed($places)` | `(string)$decimal->withScale($places)` |
| `Decimal::sum()` | `Decimal::sum()` |
| `Decimal::avg()` | `Decimal::average($values, $scale)` |
| Precision as significant digits | Scale as decimal places |

There are no methods named `rem()`, `ln()`, `exp()`, `log10()` or `parity()` here.
For a quotient and remainder, use [`divideWithRemainder()`](../guide/arithmetic#quotient-and-remainder).
Precision and scale differ between the libraries, so review rounding and division
when migrating calculations.

## History

The library was written in 2019 for the [Spryker](https://spryker.com/) commerce framework and has been used there in order and price calculations since. This repository is the independently maintained continuation for the PHP ecosystem. The original for older PHP versions is [spryker/decimal-object](https://github.com/spryker/decimal-object).
