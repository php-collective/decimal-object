# Conversion

## String

A `Decimal` casts to its exact decimal string, including trailing zeros:

```php
use PhpCollective\DecimalObject\Decimal;

$value = Decimal::create('19.90');

echo $value;              // 19.90
$string = (string)$value;
$string = $value->toString();
```

Feeding that string back into `Decimal::create()` gives an equal object. This is the format to store and to send over an API.

The scale comes back as the number of decimals in the string. If you created the object with a larger scale, such as `Decimal::create('0.1', 4)`, the string is still `0.1`, so pass the scale again when you recreate it.

## JSON

`Decimal` implements `JsonSerializable` and encodes as a string:

```php
echo json_encode(['total' => Decimal::create('19.90')]);
// {"total":"19.90"}
```

A JSON number would be read back as a float by most clients, which defeats the purpose.

## Integer

`toInt()` returns whole numbers as they are. A value with decimals needs a rounding mode, otherwise a `RoundingNecessaryException` is thrown:

```php
echo Decimal::create('23.00')->toInt();                       // 23
echo Decimal::create('-23.74')->toInt(Decimal::ROUND_DOWN);    // -23, same as an int cast
echo Decimal::create('-23.74')->toInt(Decimal::ROUND_HALF_UP); // -24
```

Values outside the range of a PHP integer throw an `OverflowException`. Check with `fitsInt()` first when the value may be large:

```php
$value = Decimal::create('9223372036854775808');

if ($value->fitsInt()) {
    $int = $value->toInt();
}
```

## Float

```php
var_dump(Decimal::create('-23.44')->toFloat()); // float(-23.44)
```

A float holds about 15 significant digits, so longer values come back as the nearest float:

```php
var_dump(Decimal::create('0.12345678901234567890')->toFloat()); // float(0.12345678901234568)
```

Use it for display libraries and charts, not for further calculation. A value beyond the float range throws an `OverflowException`.

> [!TIP] Changed in 2.0
> `toFloat()` used to refuse values with long fractions, and both conversions threw a `TypeError`. See [Upgrading](../reference/upgrading#from-1-x-to-2-0).

## Scientific notation

```php
echo Decimal::create('-22.345')->toScientific(); // -2.2345e1
echo Decimal::create('0.00230')->toScientific(); // 2.30e-3
echo Decimal::create('1200')->toScientific();    // 1.2e3
echo Decimal::create('0')->toScientific();       // 0e0
```

Decimals are kept as written, so the scale survives a round trip through `Decimal::create()`. Call `trim()` first for the shortest form.

## Serialization

`serialize()` stores the decimal string only:

```php
echo serialize(Decimal::create('19.90'));
// O:35:"PhpCollective\DecimalObject\Decimal":1:{s:5:"value";s:5:"19.90";}
```

Objects serialized by 1.x are still read. For storage that other systems read too, prefer the plain string.

## Localized output

The object has no formatting of its own. Pass the string to `NumberFormatter` or to your framework's number helper. Most formatters convert to float internally, so check their precision limits for large values.

## Debugging

`var_dump()` and similar tools show the value and the scale instead of the internal parts:

```php
var_dump(Decimal::create('1.10'));
```

```
object(PhpCollective\DecimalObject\Decimal)#2 (2) {
  ["value"]=>
  string(4) "1.10"
  ["scale"]=>
  int(2)
}
```
