---
title: "Writer"
description: "One output format."
order: 100
generated: true
---

`Waypoint\Export\Writer` · interface · namespace [`Waypoint\Export`](index.md)

```php
interface Writer
```

One output format. Register extra writers from another plugin through Writers::add() in boot().

## Methods

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

- `$sheet` [`Sheet`](Sheet.md)

**Returns** `iterable<string>`
