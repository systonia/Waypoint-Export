---
title: "CsvWriter"
description: "CSV in the configured delimiter, line ending and encoding; a BOM when UTF-8."
order: 100
generated: true
---

`Waypoint\Export\Writers\CsvWriter` · final class · namespace [`Waypoint\Export\Writers`](index.md)

```php
final class CsvWriter implements \Waypoint\Export\Writer
```

Implements [`Writer`](../Writer.md)

CSV in the configured delimiter, line ending and encoding; a BOM when UTF-8.

## Methods

### __construct()

```php
public function __construct(
    private readonly \Waypoint\Export\Cells $cells,
    \Waypoint\Export\ExportOptions $options,
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
