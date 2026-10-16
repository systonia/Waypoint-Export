---
title: Configuration
description: ExportOptions with its CSV and XML settings, and the values for other spreadsheet locales.
order: 50
---

The plugin reads [`ExportOptions`](../reference/Waypoint/Export/ExportOptions.md), set like every Waypoint Options class with `$app->configure()`:

```php
use Waypoint\Export\ExportOptions;

$app->plugin(new ExportPlugin());
$app->configure(function (ExportOptions $options) {
    $options->csv->encoding = 'UTF-8';
});
```

The plugin builds its writers from the options once, when the app boots, so every setting applies to every export of the app.

## Values in the text formats

These shape CSV, the HTML download, the HTML table in views and the text shown in ODS cells. JSON Lines and XML ignore them; they always write `.` decimals, `true`/`false` and ISO 8601.

| Setting | Default | |
|---|---|---|
| `dateFormat` | `'d.m.Y H:i'` | a [PHP date format](https://www.php.net/manual/en/datetime.format.php) |
| `decimalSeparator` | `','` | replaces the `.` of numbers |
| `booleans` | `['1', '0']` | the words for `true` and `false` |
| `spreadsheetDateFormat` | `'dd.mm.yyyy hh:mm'` | the number format of date cells in XLSX |

`spreadsheetDateFormat` is a spreadsheet number format code, not a PHP one: `dd.mm.yyyy` for the date only, `yyyy-mm-dd hh:mm` for ISO style.

## CSV

`ExportOptions::$csv` is a [`CsvOptions`](../reference/Waypoint/Export/CsvOptions.md):

| Setting | Default | |
|---|---|---|
| `delimiter` | `';'` | the list separator a spreadsheet program expects for its region: `;` in most of Europe, `,` in the US and UK |
| `encoding` | `'Windows-1252'` | or `'UTF-8'`, then written with a byte order mark so spreadsheet programs detect it |
| `lineEnding` | `"\r\n"` | |
| `guardFormulas` | `true` | prefix text cells starting with `=`, `+`, `-`, `@` with `'` |

The defaults open as columns, with numbers and umlauts intact, when a CSV is double-clicked on a German or other European Windows system. For a US/UK spreadsheet program:

```php
$app->configure(function (ExportOptions $options) {
    $options->csv->delimiter = ',';
    $options->decimalSeparator = '.';
    $options->dateFormat = 'm/d/Y g:i A';
});
```

For files that a program reads rather than a person, UTF-8 keeps every character and `\n` is the usual line ending:

```php
$app->configure(function (ExportOptions $options) {
    $options->csv->encoding = 'UTF-8';
    $options->csv->lineEnding = "\n";
    $options->csv->delimiter = ',';
    $options->decimalSeparator = '.';
    $options->booleans = ['true', 'false'];
});
```

Turn `guardFormulas` off only when the CSV never reaches a spreadsheet program and a leading `'` would change the data, for instance negative amounts stored as text like `-5 EUR`.

The encoding is any name PHP's `mb_convert_encoding()` knows; characters it cannot represent become `?`. The `Content-Type` names it as charset (`text/csv; charset=windows-1252`).

## XML

`ExportOptions::$xml` is an [`XmlOptions`](../reference/Waypoint/Export/XmlOptions.md):

| Setting | Default | |
|---|---|---|
| `validate` | `false` | check each document against its XSD before it is sent, and fail with a `RuntimeException` |
| `rowElement` | `'row'` | the element name of one row |

```php
use Waypoint\Environment;

$app->configure(function (ExportOptions $options, Environment $env) {
    $options->xml->validate = $env->isDev();
    $options->xml->rowElement = 'customer';
});
```

See [XML with a schema](xml-schema.md) for what validation checks.

## Per export

The options are app-wide. What differs per export is set on the sheet: the format (`Sheet::file('x.dat', 'csv')`), the title, the column names and types, and the XML schema. The cell values are yours to shape in a column closure — a date formatted as a string in the closure is written as that text:

```php
->column('day', 'Day', fn(Order $o): string => $o->placed->format('Y-m-d'))
```

See also: [Formats and cell types](formats.md), [`ExportOptions`](../reference/Waypoint/Export/ExportOptions.md).
