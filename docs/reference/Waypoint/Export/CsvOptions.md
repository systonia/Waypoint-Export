---
title: "CsvOptions"
description: "`ExportOptions::$csv`."
order: 100
generated: true
---

`Waypoint\Export\CsvOptions` · class · namespace [`Waypoint\Export`](index.md)

```php
class CsvOptions
```

`ExportOptions::$csv`.

## Properties

### $delimiter

```php
public string $delimiter = ';';
```

Field separator; Excel expects the list separator of its locale (';' in most of Europe, ',' in the US).

### $encoding

```php
public string $encoding = 'Windows-1252';
```

'Windows-1252' (Excel opens it directly) or 'UTF-8' (written with a BOM so Excel detects it).

### $lineEnding

```php
public string $lineEnding = '
';
```

"\r\n" is what Excel writes itself.

### $guardFormulas

```php
public bool $guardFormulas = true;
```

Prefix cells starting with =, +, -, @ with a quote so a spreadsheet never runs them as formulas (CSV injection).
