---
title: "ZipPackage"
description: "A zip built in a temp file from strings and temp files, streamed out in chunks; shared by XLSX and ODS."
order: 100
generated: true
---

`Waypoint\Export\Writers\ZipPackage` · final class · namespace [`Waypoint\Export\Writers`](index.md)

```php
final class ZipPackage
```

A zip built in a temp file from strings and temp files, streamed out in chunks; shared by XLSX and ODS.

## Methods

### __construct()

```php
public function __construct()
```

Opens a new archive in a temp file.

### addString()

```php
public function addString(string $name, string $content, bool $store = false): \Waypoint\Export\Writers\ZipPackage
```

Adds an entry from a string; $store keeps it uncompressed (ODS needs that for `mimetype`).

**Parameters**

- `$name` `string`
- `$content` `string`
- `$store` `bool`

**Returns** [`ZipPackage`](ZipPackage.md)

### addFile()

```php
public function addFile(string $name, string $path): \Waypoint\Export\Writers\ZipPackage
```

A file written through tempFile(); deleted once the archive has been streamed.

**Parameters**

- `$name` `string`
- `$path` `string`

**Returns** [`ZipPackage`](ZipPackage.md)

### tempFile()

```php
public function tempFile(): string
```

A scratch file a writer can append the big part (the sheet XML) to.

**Returns** `string`

### stream()

```php
public function stream(): iterable
```

**Returns** `iterable<string>`
