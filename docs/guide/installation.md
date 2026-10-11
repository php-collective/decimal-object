# Installation

```sh
composer require php-collective/decimal-object
```

## Enable bcmath

Composer checks PHP and extension compatibility. Enable `ext-bcmath` for the CLI and for the web server, as they often load different `php.ini` files.

::: tip Older PHP versions
PHP 8.1 is served by the 1.3 releases, which Composer selects on its own. For PHP 7 use the original [spryker/decimal-object](https://github.com/spryker/decimal-object).
:::

Continue with [Getting started](./getting-started).
