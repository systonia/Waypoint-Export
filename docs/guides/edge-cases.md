---
title: Errors and limits
description: File names, unknown formats, memory use, encodings, and what happens when an export fails.
order: 90
---

## File names

`Sheet::file()` throws an `InvalidArgumentException` for

- a name with `/`, `\`, `:`, `*`, `?`, `"`, `<`, `>`, `|` or a control character (`'../etc/passwd.csv'`, `'dir/report.csv'`),
- a name without an extension and without a format (`'README'`),
- a format that is not lowercase letters and digits (`Sheet::file('report', 'CSV')`).

Spaces and non-ASCII letters are fine: `Sheet::file('Bericht März 2026.xlsx')` is sent as `filename="Bericht März 2026.xlsx"; filename*=UTF-8''Bericht%20M%C3%A4rz%202026.xlsx`, and browsers use the encoded form. An extension with more dots counts only the last part: `a.tar.gz` asks for the format `gz`.

## Unknown formats

A sheet whose format no writer handles fails when the route's result is rendered: `Writers::for()` throws a `RuntimeException` like `No export writer for 'pdf'; installed: csv, jsonl, html, xml, xlsx, ods.`. Waypoint logs it and answers 500 with a generic problem body.

When the format comes from the URL, a missing writer is the client's mistake. Ask the registry and answer 404:

```php
use Waypoint\Exceptions\NotFoundException;
use Waypoint\Export\{Sheet, Writers};

#[Controller('/reports')]
final class ReportsController
{
    #[Inject]
    private Writers $writers;

    #[Get('/costs.{format}')]
    public function costs(string $format): Sheet
    {
        if (!$this->writers->has($format)) {
            throw new NotFoundException("No export as .$format");
        }
        return Sheet::file("costs.$format")->columns(['item' => 'Item'])->rows($this->costs->all());
    }
}
```

## Errors while writing

The file is written after the controller has returned, while the response is rendered. An exception from a column closure, from the rows' generator (a lost database connection) or from XML validation propagates from there, and the request answers 500. Waypoint discards what was written before the exception, so no half-written file is sent.

## Memory

Each writer reads the rows one at a time and yields the file in chunks, so the rows never have to be in memory as a whole: pass a generator to `rows()` for large tables.

- CSV, JSON Lines, HTML and XML yield one chunk per row.
- XLSX and ODS write the sheet row by row into a temp file in `sys_get_temp_dir()`, zip it, and yield the archive in 64 KiB chunks; the temp files are deleted afterwards.
- With `xml->validate`, the XML document is built as a whole first, then validated.

Waypoint's `Response` collects what is written into its body, so the finished file is held once in memory before it is sent. A file of several hundred megabytes needs a matching `memory_limit`.

## Encodings

CSV in Windows-1252 cannot hold every character: `€`, `ß` and the umlauts are fine, `✓` or `東京` become `?`. When the data has such characters, use `csv->encoding = 'UTF-8'` (written with a byte order mark) or one of the spreadsheet formats, which are always UTF-8 inside.

## Numbers and text

- A numeric string stays text: `'007'` keeps its zeros in every format, and XLSX stores it as text. Convert in a closure (`fn($r) => (int) $r['qty']`) when a database returns numbers as strings and the spreadsheet should calculate with them.
- Floats are written with at most six decimals (except in JSON Lines). For money, pass the amount as it should appear, for instance rounded to two decimals.
- A backed enum is written as its value; a pure enum has no value and no JSON form, so it comes out as empty text; give such a column a closure returning `$case->name`.
- Arrays and objects without `__toString()` become their JSON.

## Spreadsheet names and element names

- XLSX sheet names are cut to 31 characters and `\ / ? * [ ] :` become `_`, which spreadsheet programs require.
- XML element names lose every character outside letters, digits, `_`, `-` and `.`; a name starting with a digit gets a leading `_`.

## Rows are read once

`rows()` keeps the iterable as it is and each write iterates it. An array can be written any number of times; a generator only once. Writing the same sheet twice throws `Cannot traverse an already closed generator`. Build a new sheet per response, as a route does anyway.
