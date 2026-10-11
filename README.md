#  Decimal Object

[![CI](https://github.com/php-collective/decimal-object/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/php-collective/decimal-object/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/php-collective/decimal-object/branch/master/graph/badge.svg?token=L1thFB9nOG)](https://codecov.io/gh/php-collective/decimal-object)
[![Latest Stable Version](https://poser.pugx.org/php-collective/decimal-object/v/stable.svg)](https://packagist.org/packages/php-collective/decimal-object)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![License](https://poser.pugx.org/php-collective/decimal-object/license)](https://packagist.org/packages/php-collective/decimal-object)

Decimal value object for PHP.

## Documentation

**<https://php-collective.github.io/decimal-object/>**

## Background

Floats cannot represent most decimal fractions exactly, which makes them unsuitable for money and other values that must add up:

```php
var_dump(0.1 + 0.2 === 0.3); // bool(false)
```

Handling such values as strings works, and a value object keeps the logic for it in one place:

```php
use PhpCollective\DecimalObject\Decimal;

$total = Decimal::create('0.1')->add('0.2');

echo $total;                  // 0.3
var_dump($total->equals('0.3')); // bool(true)
```

## Features

- Strict about precision and scale. Significant digits are only dropped when you `trim()` or round.
- Readable API: `add()`, `greaterThan()`, `isZero()`.
- Arithmetic, rounding, comparison and conversion.
- Immutable.
- Handles numbers beyond the integer and float range.
- Calculates with PHP's `bcmath` extension.

## Installation

Follow [Installation](https://php-collective.github.io/decimal-object/guide/installation)
to install the package and enable bcmath for the PHP process that runs it.

Requires PHP 8.2+ and the `bcmath` extension. The 1.x API is maintained in the 1.4 releases, see [Upgrading](https://php-collective.github.io/decimal-object/reference/upgrading) for the changes. PHP 8.1 is covered by the 1.3 releases. For anything older, use the [legacy library](https://github.com/spryker/decimal-object).

## Usage

- [Getting started](https://php-collective.github.io/decimal-object/guide/getting-started)
- [Arithmetic](https://php-collective.github.io/decimal-object/guide/arithmetic)
- [Precision and scale](https://php-collective.github.io/decimal-object/guide/scale)
- [Rounding](https://php-collective.github.io/decimal-object/guide/rounding)
- [API reference](https://php-collective.github.io/decimal-object/reference/api)

## Integrations

- [dereuromark/cakephp-decimal](https://dereuromark.github.io/cakephp-decimal/) as decimal type for the CakePHP ORM.

See [Alternatives](https://php-collective.github.io/decimal-object/reference/alternatives) for how this library compares to php-decimal, brick/math and the money libraries.

## Contributing

Pull requests are welcome:

- Target `master` for bug fixes and backwards compatible improvements.
- Target `next` (the next major version) for anything that breaks backwards compatibility.

## History

The library was written in 2019 for the [Spryker](https://spryker.com/) commerce framework and has been used there in order and price calculations since. This repository is the independently maintained continuation for the PHP ecosystem as a whole.
