---
title: "XmlWriter"
description: "`<title xmlns=\"...\"><row><col>...</col></row>...</title>`; empty cells are `xsi:nil`."
order: 100
generated: true
---

`Waypoint\Export\Writers\XmlWriter` · final class · namespace [`Waypoint\Export\Writers`](index.md)

```php
final class XmlWriter implements \Waypoint\Export\Writer
```

Implements [`Writer`](../Writer.md)

`<title xmlns="..."><row><col>...</col></row>...</title>`; empty cells are `xsi:nil`.
xsd() derives a schema from the columns (declared types, else xs:string), and with
XmlOptions::$validate every document is checked against it before it leaves.

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

### xsd()

```php
public function xsd(\Waypoint\Export\Sheet $sheet): string
```

An XML Schema for the sheet: the root, unbounded rows, one nillable element per column.

**Parameters**

- `$sheet` [`Sheet`](../Sheet.md)

**Returns** `string`

### name()

```php
public static function name(string $text): string
```

A usable element name: letters, digits, '_', '-', '.'; anything else becomes '_'.

**Parameters**

- `$text` `string`

**Returns** `string`
