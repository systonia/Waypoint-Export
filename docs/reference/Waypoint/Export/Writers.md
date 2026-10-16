---
title: "Writers"
description: "The registered writers by extension; ExportPlugin puts one in the container."
order: 100
generated: true
---

`Waypoint\Export\Writers` · final class · namespace [`Waypoint\Export`](index.md)

```php
final class Writers
```

The registered writers by extension; ExportPlugin puts one in the container.

## Methods

### add()

```php
public function add(\Waypoint\Export\Writer $writer): \Waypoint\Export\Writers
```

Registers $writer under its lowercased extension; one already registered for it is replaced.

**Parameters**

- `$writer` [`Writer`](Writer.md)

**Returns** [`Writers`](Writers.md)

### for()

```php
public function for(string $format): \Waypoint\Export\Writer
```

The writer for $format, case-insensitively.

**Parameters**

- `$format` `string`

**Returns** [`Writer`](Writer.md)

**Throws**

- [`RuntimeException`](https://www.php.net/manual/en/class.runtimeexception.php): naming the installed formats when none matches

### has()

```php
public function has(string $format): bool
```

Whether a writer for $format is installed, case-insensitively.

**Parameters**

- `$format` `string`

**Returns** `bool`

### all()

```php
public function all(): array
```

**Returns** `array<string, `[`Writer`](Writer.md)`>`: extension => writer
