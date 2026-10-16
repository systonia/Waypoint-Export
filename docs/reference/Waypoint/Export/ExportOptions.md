---
title: "ExportOptions"
description: "Configure with `$app->configure(function (ExportOptions $opts) { ... })`."
order: 100
generated: true
---

`Waypoint\Export\ExportOptions` · class · namespace [`Waypoint\Export`](index.md)

```php
class ExportOptions
```

Configure with `$app->configure(function (ExportOptions $opts) { ... })`. Defaults open cleanly in a German/European Excel.

## Properties

### $dateFormat

```php
public string $dateFormat = 'd.m.Y H:i';
```

Date format in the text formats (CSV, HTML, fixed width); JSONL and XML always write ISO 8601.

### $spreadsheetDateFormat

```php
public string $spreadsheetDateFormat = 'dd.mm.yyyy hh:mm';
```

Number format code for date cells in XLSX.

### $decimalSeparator

```php
public string $decimalSeparator = ',';
```

Decimal separator for floats in the text formats; ',' is what a German Excel reads as a number.

### $booleans

```php
public array $booleans = ['1', '0'];
```

**Type** `array{string, string}`

How booleans read in the text formats: [true, false].

### $csv

```php
public \Waypoint\Export\CsvOptions $csv;
```

Settings of the CSV writer.

### $xml

```php
public \Waypoint\Export\XmlOptions $xml;
```

Settings of the XML writer.

## Methods

### __construct()

```php
public function __construct()
```
