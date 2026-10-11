# Creating decimals

```php
use PhpCollective\DecimalObject\Decimal;

$a = Decimal::create('1.234');
$b = new Decimal('1.234');
```

Both forms accept numeric values and `Stringable` objects, including an existing `Decimal`. Without an explicit scale, `create()` clones an instance of the called class and preserves its declared scale; the constructor reads the string value and detects its scale again.

## Accepted input

| Input | Example | Result |
| --- | --- | --- |
| Decimal string | `'-5.000067'` | `-5.000067` |
| Integer | `-25000` | `-25000` |
| Float | `1.1` | `1.1` |
| Scientific notation | `'6.22e8'`, `'2.2e-6'` | `622000000`, `0.0000022` |
| `Stringable` object with a numeric string | another `Decimal` | same value |

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

Anything that is not a plain number throws an `InvalidArgumentException`:

```php
Decimal::create('abc');   // Invalid non numeric value
Decimal::create('1,5');   // Invalid non numeric value
Decimal::create(INF);     // Invalid non finite value
```

Localized strings such as `'1.018,90'` are rejected. Parse them with `NumberFormatter` or your framework's locale tools first.

::: warning Floats
A float is converted with PHP's own float to string rules, which round to the `precision` ini setting (14 digits by default). That hides small errors but also drops real digits. For money and other exact values, read the number as a string from the request or the database.
:::

## Floats with all their digits

When a float is all you have, `createFromFloat()` keeps every digit needed to get the same float back, independent of ini settings:

```php
echo Decimal::create(1234567890.1234567);          // 1234567890.1235
echo Decimal::createFromFloat(1234567890.1234567); // 1234567890.1234567

echo Decimal::create(0.1 + 0.2);          // 0.3
echo Decimal::createFromFloat(0.1 + 0.2); // 0.30000000000000004
```

The second pair shows the trade: the result is what the float really holds, including its error.

## Zero and one

```php
$total = Decimal::zero();
$factor = Decimal::one();
```

> [!TIP] Added in 1.4
> `createFromFloat()`, `zero()` and `one()`.

::: info Exponent limit
Exponents beyond `Decimal::MAX_EXPONENT` (10000 in either direction) throw an `InvalidArgumentException`. Without a limit, the twelve characters of `'1e1000000000'` would expand to a string of a billion digits. A subclass can redefine the constant.
:::

## Optional input

`tryCreate()` returns `null` for `null` or any input rejected with `InvalidArgumentException`, including non-numeric or non-finite values, excessive exponents and negative scales:

```php
Decimal::tryCreate('1.50'); // Decimal 1.50
Decimal::tryCreate('abc');  // null
Decimal::tryCreate('');     // null
Decimal::tryCreate(null);   // null
```

This fits optional form fields and nullable columns.

> [!TIP] Added in 1.4
> `tryCreate()` and the exponent limit.

## Setting a scale

The second argument fixes the number of decimal places:

```php
echo Decimal::create('0.129', 2); // 0.12
```

Digits beyond the scale are cut off, not rounded. Use [`round()`](./rounding) when you want rounding.

A scale larger than the input records the scale without padding the string. Results of later calculations use it:

```php
$value = Decimal::create('0.1', 4);

echo $value;             // 0.1
echo $value->scale();    // 4
echo $value->add('0.2'); // 0.3000
```

::: warning A scale of 0
`0` is read as "no scale given" and does not cut off decimals: `Decimal::create('1.5', 0)` stays `1.5`. Its declared scale is still 0, so later arithmetic can truncate fractional digits: adding `1` produces `2`. Comparisons use the stored value. Use `truncate()` or `round()` to get a whole number. The next major version will treat `0` like any other scale.
:::

### Strict mode

Pass `true` as the third argument to refuse input that would lose digits:

```php
Decimal::create('0.123', 2, true);
// InvalidArgumentException: Loss of precision detected. Detected scale `3` > `2` as defined.
```

This is useful when the scale mirrors a database column and silent truncation would hide a bug.

## Subclassing

All factory and arithmetic methods return `static`, so a subclass such as `Money extends Decimal` gets its own type back from every operation.
