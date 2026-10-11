# Alternatives

This library is one way to handle decimals in PHP. Depending on the project another one may fit better.

| Library | Notes |
| --- | --- |
| [php-decimal](https://php-decimal.github.io/) | A PHP extension. Faster, and it supports the arithmetic operators, but it has to be installed on every server. Works with precision (significant digits) where this library works with scale. |
| [brick/math](https://github.com/brick/math) | `BigDecimal`, `BigInteger` and `BigRational` with several calculation engines and more rounding modes. |
| [brick/money](https://github.com/brick/money), [moneyphp/money](https://github.com/moneyphp/money) | Money with currencies, allocation and formatting. Choose one of these when you need currency handling and not only a number. |

## Coming from the php-decimal extension

| php-decimal | Decimal Object |
| --- | --- |
| Operators `+`, `-`, `*`, `/` | Methods `add()`, `subtract()`, `multiply()`, `divide()` |
| `abs()` | `absolute()` |
| `signum()` | `sign()` |
| `sub()`, `mul()`, `div()` | `subtract()`, `multiply()`, `divide()` |
| Precision as significant digits | Scale as decimal places |

Not available here: `rem()`, `shift()`, `ln()`, `exp()`, `log10()`, `parity()`, `isEven()`, `isOdd()`, `toFixed()`, `Decimal::sum()` and `Decimal::avg()`.

## History

The library was written in 2019 for the [Spryker](https://spryker.com/) commerce framework and has been used there in order and price calculations since. This repository is the independently maintained continuation for PHP 8. The original for older PHP versions is [spryker/decimal-object](https://github.com/spryker/decimal-object).
