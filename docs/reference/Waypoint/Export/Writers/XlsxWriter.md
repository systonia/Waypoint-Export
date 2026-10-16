---
title: "XlsxWriter"
description: "A minimal, valid OOXML workbook: one sheet, inline strings, real numbers and booleans, dates as serials with a number format, bold header."
order: 100
generated: true
---

`Waypoint\Export\Writers\XlsxWriter` · final class · namespace [`Waypoint\Export\Writers`](index.md)

```php
final class XlsxWriter implements \Waypoint\Export\Writer
```

Implements [`Writer`](../Writer.md)

A minimal, valid OOXML workbook: one sheet, inline strings, real numbers and booleans, dates as serials with a number format, bold header.

## Methods

### __construct()

```php
public function __construct(
    private readonly \Waypoint\Export\Cells $cells,
    private readonly \Waypoint\Export\ExportOptions $options,
)
```

**Parameters**

- `$cells` [`Cells`](../Cells.md)
- `$options` [`ExportOptions`](../ExportOptions.md)

### extension()

```php
public function extension(): string
```

The file extension the writer answers to; `Sheet::file('x.<extension>')` selects it.

**Returns** `string`

### contentType()

```php
public function contentType(): string
```

The Content-Type header of the download, with a charset for text formats.

**Returns** `string`

### stream()

```php
public function stream(\Waypoint\Export\Sheet $sheet): iterable
```

The file, chunk by chunk -- a generator, so a large export never sits in memory twice.

**Parameters**

- `$sheet` [`Sheet`](../Sheet.md)

**Returns** `iterable<string>`

### ref()

```php
public static function ref(int $column, int $row): string
```

A1-style reference for a zero-based column and one-based row.

**Parameters**

- `$column` `int`
- `$row` `int`

**Returns** `string`
