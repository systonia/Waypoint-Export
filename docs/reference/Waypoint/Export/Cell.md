---
title: "Cell"
description: "A typed cell: Text carries a string, Number an int|float, Bool a bool, Date a DateTimeInterface, Empty null."
order: 100
generated: true
---

`Waypoint\Export\Cell` · final class · namespace [`Waypoint\Export`](index.md)

```php
final class Cell
```

A typed cell: Text carries a string, Number an int|float, Bool a bool, Date a DateTimeInterface, Empty null.

## Properties

### $type

```php
public readonly \Waypoint\Export\CellType $type;
```

### $value

```php
public readonly \DateTimeInterface|string|int|float|bool|null $value;
```

## Methods

### __construct()

```php
public function __construct(
    public readonly \Waypoint\Export\CellType $type,
    public readonly \DateTimeInterface|string|int|float|bool|null $value,
)
```

**Parameters**

- `$type` [`CellType`](CellType.md)
- `$value` [`DateTimeInterface`](https://www.php.net/manual/en/class.datetimeinterface.php)`|string|int|float|bool|null`
