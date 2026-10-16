---
title: "Cells"
description: "The one place that reads a row and types a value."
order: 100
generated: true
---

`Waypoint\Export\Cells` · final class · namespace [`Waypoint\Export`](index.md)

```php
final class Cells
```

The one place that reads a row and types a value. Writers get Cells and
choose a representation: text() for human formats, machine() for JSONL/XML.

## Methods

### __construct()

```php
public function __construct(private readonly \Waypoint\Export\ExportOptions $options)
```

text() takes the decimal separator, date format and boolean words from $options.

**Parameters**

- `$options` [`ExportOptions`](ExportOptions.md)

### row()

```php
public function row(array $columns, mixed $row): array
```

Every cell of one row, in column order.

**Parameters**

- `$columns` `array<int, array{key: string, value: `[`Closure`](https://www.php.net/manual/en/class.closure.php)`|null}>`
- `$row` `mixed`

**Returns** `list<`[`Cell`](Cell.md)`>`

### read()

```php
public static function read(mixed $row, string $key): mixed
```

The row's key (array) or public property (object); null when absent.

**Parameters**

- `$row` `mixed`
- `$key` `string`

**Returns** `mixed`

### cell()

```php
public function cell(mixed $value): \Waypoint\Export\Cell
```

Types one value: null is Empty, bool Bool, int and float Number, a DateTimeInterface Date,
a backed enum its value, a string or Stringable Text, anything else Text holding its JSON.

**Parameters**

- `$value` `mixed`

**Returns** [`Cell`](Cell.md)

### text()

```php
public function text(\Waypoint\Export\Cell $cell): string
```

Human representation: options' decimal separator, date format and boolean words.

**Parameters**

- `$cell` [`Cell`](Cell.md)

**Returns** `string`

### machine()

```php
public static function machine(\Waypoint\Export\Cell $cell): string
```

Machine representation: '.' decimals, ISO 8601 dates, true/false.

**Parameters**

- `$cell` [`Cell`](Cell.md)

**Returns** `string`

### number()

```php
public static function number(mixed $value): string
```

Ints as-is, floats without trailing zeros (up to six decimals), never in exponent notation.

**Parameters**

- `$value` `mixed`

**Returns** `string`

### guardFormula()

```php
public static function guardFormula(string $text): string
```

A leading quote for cells a spreadsheet would run as a formula; numbers are left alone.

**Parameters**

- `$text` `string`

**Returns** `string`

### serial()

```php
public static function serial(\DateTimeInterface $date): float
```

Excel/ODS serial day number (days since 1899-12-30, local time) for a date.

**Parameters**

- `$date` [`DateTimeInterface`](https://www.php.net/manual/en/class.datetimeinterface.php)

**Returns** `float`
