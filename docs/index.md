---
title: Waypoint Export
description: Return a Sheet from a Waypoint route and the browser downloads it as CSV, JSON Lines, HTML, XML, XLSX or ODS.
order: 1
---

Waypoint Export turns a table into a file download. A route returns a `Sheet` (a file name, columns and rows), and the plugin writes it in the format the file's extension names:

| Extension | Format | Content-Type |
|---|---|---|
| `.csv` | CSV for spreadsheet programs | `text/csv; charset=windows-1252` (or `utf-8`) |
| `.jsonl` | JSON Lines, one object per row | `application/x-ndjson; charset=utf-8` |
| `.html` | a standalone page with one table | `text/html; charset=utf-8` |
| `.xml` | XML, optionally with a namespace and a generated XSD | `application/xml; charset=utf-8` |
| `.xlsx` | an Office Open XML workbook | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |
| `.ods` | an OpenDocument spreadsheet | `application/vnd.oasis.opendocument.spreadsheet` |

All six are written by the package itself, without external libraries; XLSX and ODS use PHP's `zip` extension. The same sheet also renders as an HTML table inside a view, and other plugins can add formats: [waypoint-export-fixed](https://github.com/systonia/Waypoint-Export-Fixed) adds fixed-width record files this way.

## Install

```bash
composer require systonia/waypoint-export
```

The package needs PHP 8.5 and `systonia/waypoint` 0.6.4 or later. Register the plugin before `attach()`, as the Sonnenkorn bakery does in its [`app.php`](https://github.com/systonia/Waypoint-Bakery/blob/main/app.php):

```php
use Waypoint\Export\{ExportOptions, ExportPlugin};
use Waypoint\Waypoint;

$app = Waypoint::create();
$app->plugin(new ExportPlugin());
$app->configure(function (ExportOptions $export) {
    $export->csv->encoding = 'UTF-8';
});
$app->attach([BackofficeController::class]);
$app->serve();
```

## A first export

The bakery's owner downloads the orders of the last 90 days from the back office. Shortened from `src/Controllers/BackofficeController.php`:

```php
<?php

namespace Bakery\Controllers;

use Bakery\Models\{Order, OrderLine};
use Bakery\Repositories\Orders;
use DateTimeImmutable;
use Waypoint\Attributes\{Authenticated, Controller, Get, Ignore, Inject, Role};
use Waypoint\Exceptions\NotFoundException;
use Waypoint\Export\Sheet;
use Waypoint\I18n\Translator;

#[Controller('/backoffice')]
#[Ignore]
#[Authenticated]
final class BackofficeController
{
    #[Inject]
    private Orders $orders;

    #[Inject]
    private Translator $translator;

    /** The last 90 days of orders as orders.csv or orders.xlsx. */
    #[Get('/orders.{format}')]
    #[Role('owner')]
    public function export(string $format): Sheet
    {
        if (!in_array($format, ['csv', 'xlsx'], true)) {
            throw new NotFoundException("No export as $format");
        }
        return $this->sheet("orders.$format", $this->orders->recent(90));
    }

    /** @param list<Order> $orders */
    private function sheet(string $file, array $orders): Sheet
    {
        $placed = fn (Order $o) => new DateTimeImmutable($o->createdAt);
        $customer = fn (Order $o) => $o->customerName;
        $status = fn (Order $o) => $o->status->label($this->translator);
        $items = fn (Order $o) => implode(', ', array_map(fn (OrderLine $l) => "$l->quantity × $l->name", $o->lines));
        $total = fn (Order $o) => $o->totalCents() / 100;
        return Sheet::file($file)
            ->title('Orders')
            ->column('code', 'Order')
            ->column('created', 'Placed', $placed)
            ->column('customer', 'Customer', $customer)
            ->column('email', 'Email')
            ->column('status', 'Status', $status)
            ->column('items', 'Items', $items)
            ->column('total', 'Total (€)', $total)
            ->rows($orders);
    }
}
```

`GET /backoffice/orders.csv` answers with

```http
HTTP/1.1 200 OK
Content-Type: text/csv; charset=utf-8
Content-Disposition: attachment; filename="orders.csv"; filename*=UTF-8''orders.csv
Cache-Control: no-store
```

and this file (UTF-8 with a byte order mark, `\r\n` line endings):

```text
Order;Placed;Customer;Email;Status;Items;Total (€)
3f9a1c07be;16.10.2026 18:42;Lena;lena@example.test;Received;2 × Spelt rolls (4), 1 × Butter croissant;8,6
81d2e4a90c;16.10.2026 20:05;Paul;paul@example.test;Not confirmed yet;1 × Sourdough loaf;5,2
```

`/backoffice/orders.xlsx` is the same table as a workbook with real numbers and dates. The route lets only `csv` and `xlsx` through and answers 404 for anything else; without that check, `/backoffice/orders.jsonl` would download as JSON Lines, and `/backoffice/orders.pdf` would fail, because no writer for `pdf` is installed.

The bakery passes every label through `$this->translator->t()` as well, so a German owner gets German headings; the status column does it here with `OrderStatus::label()`. Each row is an `Order` object: a column reads the public property of its key (`$order->code`, `$order->email`), or calls its closure (`$o->totalCents() / 100`). Rows can also be arrays, and `rows()` takes any iterable, so a generator that pages through a table works as well. `#[Ignore]` keeps the back office out of the app's OpenAPI document; [Formats and cell types](guides/formats.md#the-response) shows what an export route contributes to it.

## How these docs are organised

- **[Guides](guides/index.md)** explain one part each with examples: building a sheet, row classes, the formats and how they write each value, XML with a schema, configuration, tables in views, adding a format from another plugin, testing, and the errors and limits.
- **[Reference](reference/index.md)** has one page per class, generated from the code with `tenma docs`.

Start with [Building a sheet](guides/sheets.md) and [Formats and cell types](guides/formats.md). The defaults are made for spreadsheet programs with European settings (`;` between fields, `,` as decimal separator); [Configuration](guides/configuration.md) shows the settings for others.
