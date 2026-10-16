---
title: "Column"
description: "Marks a public property of a row class as an export column; `Sheet::columnsOf(RowClass::class)` collects them in declaration order."
order: 100
generated: true
---

`Waypoint\Export\Column` · final class · namespace [`Waypoint\Export`](index.md)

```php
final class Column
```

Marks a public property of a row class as an export column; `Sheet::columnsOf(RowClass::class)`
collects them in declaration order. $name is the JSON key / XML element (default: the property),
$type feeds the XSD and spreadsheet formats (default: derived from each value).

## Properties

### $label

```php
public string $label;
```

**Type** `string`

Header text in CSV, HTML, XLSX and ODS.

### $name

```php
public ?string $name = null;
```

**Type** `string|null`

JSON key / XML element name; default: the property name.

### $type

```php
public ?\Waypoint\Export\CellType $type = null;
```

**Type** [`CellType`](CellType.md)`|null`

Declared type for the generated XSD; default: typed per value.

## Methods

### __construct()

```php
public function __construct(
    public string $label,
    public ?string $name = null,
    public ?\Waypoint\Export\CellType $type = null,
)
```

**Parameters**

- `$label` `string` — Header text in CSV, HTML, XLSX and ODS.
- `$name` `string|null` — JSON key / XML element name; default: the property name.
- `$type` [`CellType`](CellType.md)`|null` — Declared type for the generated XSD; default: typed per value.
