---
title: "JsonLinesWriter"
description: "One JSON object per line, keyed by column name, with native types (numbers, booleans, null, ISO 8601 dates)."
order: 100
generated: true
---

`Waypoint\Export\Writers\JsonLinesWriter` · final class · namespace [`Waypoint\Export\Writers`](index.md)

```php
final class JsonLinesWriter implements \Waypoint\Export\Writer
```

Implements [`Writer`](../Writer.md)

One JSON object per line, keyed by column name, with native types (numbers, booleans, null, ISO 8601 dates).

## Methods

### __construct()

```php
public function __construct(private readonly \Waypoint\Export\Cells $cells)
```

**Parameters**

- `$cells` [`Cells`](../Cells.md)

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

The file, chunk by chunk: a generator, so a large export never sits in memory twice.

**Parameters**

- `$sheet` [`Sheet`](../Sheet.md)

**Returns** `iterable<string>`
