---
title: Waypoint Export
description: Return a Sheet from a Waypoint route and the browser downloads it as CSV, JSON Lines, HTML, XML, XLSX or ODS.
order: 1
---

Waypoint Export turns a table into a file download. A route returns a `Sheet` — a file name, columns and rows — and the plugin writes it in the format the file's extension names:

| Extension | Format | Content-Type |
|---|---|---|
| `.csv` | CSV for spreadsheet programs | `text/csv; charset=windows-1252` (or `utf-8`) |
| `.jsonl` | JSON Lines, one object per row | `application/x-ndjson; charset=utf-8` |
| `.html` | a standalone page with one table | `text/html; charset=utf-8` |
| `.xml` | XML, optionally with a namespace and a generated XSD | `application/xml; charset=utf-8` |
| `.xlsx` | an Office Open XML workbook | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |
| `.ods` | an OpenDocument spreadsheet | `application/vnd.oasis.opendocument.spreadsheet` |

All six are written by the package itself, without external libraries; XLSX and ODS use PHP's `zip` extension. The same sheet also renders as an HTML table inside a view, and other plugins can add formats — [waypoint-export-fixed](https://github.com/systonia/Waypoint-Export-Fixed) adds fixed-width record files this way.

## Install

```bash
composer require systonia/waypoint-export
```

The package needs PHP 8.5 and `systonia/waypoint` 0.6.4 or later. Register the plugin before `attach()`:

```php
use Waypoint\Export\ExportPlugin;
use Waypoint\Waypoint;

$app = Waypoint::create();
$app->plugin(new ExportPlugin());
$app->attach([ExportController::class]);
$app->serve();
```

## A first export

```php
<?php

use Waypoint\Attributes\{Controller, Get, Inject};
use Waypoint\Export\Sheet;

#[Controller('/admin')]
final class ExportController
{
    #[Inject]
    private UserRepository $users;

    #[Get('/users.{format}')]
    public function users(string $format): Sheet
    {
        return Sheet::file("users.$format")
            ->title('Users')
            ->columns(['id' => 'ID', 'email' => 'Email'])
            ->column('name', 'Name', fn(User $u): string => $u->fullName())
            ->columns(['balance' => 'Balance', 'newsletter' => 'Newsletter', 'registered' => 'Registered'])
            ->rows($this->users->all());
    }
}
```

`GET /admin/users.csv` answers with

```http
HTTP/1.1 200 OK
Content-Type: text/csv; charset=windows-1252
Content-Disposition: attachment; filename="users.csv"; filename*=UTF-8''users.csv
Cache-Control: no-store
```

and this file (Windows-1252, `\r\n` line endings):

```text
ID;Email;Name;Balance;Newsletter;Registered
1;ada@example.com;Ada Lovelace;1234,5;1;01.03.2026 09:15
2;grace@example.com;Grace Hopper;-20;0;17.05.2026 14:00
```

`/admin/users.xlsx` is the same table as a workbook with real numbers, booleans and dates, `/admin/users.jsonl` has one JSON object per user, and `/admin/users.pdf` fails, because no writer for `pdf` is installed.

Each row here is a `User` object: a column reads the public property of its key (`$user->email`), or calls its closure (`fn(User $u) => $u->fullName()`). Rows can also be arrays, and `rows()` takes any iterable, so a generator over a database cursor works as well.

## How these docs are organised

- **[Guides](guides/index.md)** explain one part each with examples: building a sheet, row classes, the formats and how they write each value, XML with a schema, configuration, tables in views, adding a format from another plugin, testing, and the errors and limits.
- **[Reference](reference/index.md)** has one page per class, generated from the code with `tenma docs`.

Start with [Building a sheet](guides/sheets.md) and [Formats and cell types](guides/formats.md). The defaults are made for spreadsheet programs with European settings (`;` between fields, `,` as decimal separator); [Configuration](guides/configuration.md) shows the settings for others.
