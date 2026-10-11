# Creating decimals

```php
use PhpCollective\DecimalObject\Decimal;

$a = Decimal::create('1.234');
$b = new Decimal('1.234');
```

Both forms take the same arguments. `create()` reads better in a chain and also accepts an existing `Decimal`.

## Accepted input

| Input | Example | Result |
| --- | --- | --- |
| Decimal string | `'-5.000067'` | `-5.000067` |
| Integer | `-25000` | `-25000` |
| Scientific notation | `'6.22e8'`, `'2.2e-6'` | `622000000`, `0.0000022` |
| `Stringable` object with a numeric string | another `Decimal` | same value |

Floats are not accepted, see [Floats](#floats).

Strings are normalized, the value stays the same:

| Input | Result |
| --- | --- |
| `'.5'` | `0.5` |
| `'+5.10'` | `5.10` |
| `'0005'` | `5` |
| `' 1.5 '` | `1.5` |
| `'-0.0'` | `0.0` |

Zero never carries a sign.

Trailing zeros are kept. `'5.10'` has a scale of 2, and the object will not drop that zero unless you call `trim()`.

## Invalid input

Anything that is not a plain number throws an `InvalidNumberException`:

```php
Decimal::create('abc');   // Invalid non numeric value
Decimal::create('1,5');   // Invalid non numeric value
```

Localized strings such as `'1.018,90'` are rejected. Parse them with `NumberFormatter` or your framework's locale tools first.

::: info Exponent limit
Exponents beyond `Decimal::MAX_EXPONENT` (10000 in either direction) throw an `InvalidNumberException`. Without a limit, the twelve characters of `'1e1000000000'` would expand to a string of a billion digits. A subclass can redefine the constant.
:::

## Floats

A float passed to the constructor, to `create()` or to any arithmetic method throws an `InvalidArgumentException`. A float has usually lost digits before it arrives, and the library refuses to guess which ones matter.

Read exact values as strings from the request or the database. When a float is all you have, convert it on purpose:

```php
echo Decimal::createFromFloat(1234567890.1234567); // 1234567890.1234567
echo Decimal::createFromFloat(0.1 + 0.2);          // 0.30000000000000004
```

`createFromFloat()` keeps every digit needed to get the same float back, independent of ini settings and locale. The second line shows what that means: you get what the float really holds, including its binary error. Round the result to the scale you expect.

> [!TIP] Changed in 2.0
> Floats were accepted everywhere and cut to 14 significant digits. See [Upgrading](../reference/upgrading#from-1-x-to-2-0).

## Optional input

`tryCreate()` returns `null` where `create()` would throw for a value that is not numeric, and passes `null` through:

```php
Decimal::tryCreate('1.50'); // Decimal 1.50
Decimal::tryCreate('abc');  // null
Decimal::tryCreate('');     // null
Decimal::tryCreate(null);   // null
```

This fits optional form fields and nullable columns. A float still throws, because that is a mistake in the code and not in the input.

## Zero and one

```php
$total = Decimal::zero();
$factor = Decimal::one();
```

## Setting a scale

The second argument fixes the number of decimal places. The string always shows it:

```php
echo Decimal::create('0.1', 4);    // 0.1000
echo Decimal::create('5', 2);      // 5.00
echo Decimal::create('0.1200', 2); // 0.12
```

A smaller scale is accepted as long as only zeros are dropped. Losing any other digit throws a `RoundingNecessaryException`:

```php
Decimal::create('0.129', 2);
// RoundingNecessaryException: The value has more than `2` decimals.
```

Say how to round instead:

```php
echo Decimal::create('0.129')->withScale(2);                      // 0.13
echo Decimal::create('0.129')->withScale(2, Decimal::ROUND_DOWN); // 0.12
```

This matters when the scale mirrors a database column: input with too many decimals is reported instead of being shortened without notice.

> [!TIP] Changed in 2.0
> A scale used to cut off surplus digits and did not pad the string. The `$strict` argument is gone; its check is now always on. See [Upgrading](../reference/upgrading#from-1-x-to-2-0).

## Subclassing

All factory and arithmetic methods return `static`, so a subclass such as `Money extends Decimal` gets its own type back from every operation.
