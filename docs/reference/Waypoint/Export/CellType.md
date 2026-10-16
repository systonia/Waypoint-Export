---
title: "CellType"
description: "What a cell holds; each Writer decides how to represent it (a real number in XLSX, `1234,5` in CSV, `1234.5` in JSONL)."
order: 100
generated: true
---

`Waypoint\Export\CellType` · enum · namespace [`Waypoint\Export`](index.md)

```php
enum CellType: string
```

What a cell holds; each Writer decides how to represent it (a real number in XLSX, `1234,5` in CSV, `1234.5` in JSONL).

## Cases

### Empty

```php
case Empty = 'empty';
```

null: an empty cell.

### Text

```php
case Text = 'text';
```

A string, a Stringable, a backed enum's value, or the JSON of an array or object.

### Number

```php
case Number = 'number';
```

An int or a float.

### Bool

```php
case Bool = 'bool';
```

A bool.

### Date

```php
case Date = 'date';
```

A DateTimeInterface.
