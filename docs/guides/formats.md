---
title: Formats and cell types
description: How each built-in writer represents numbers, dates, booleans, empty cells and text.
order: 30
---

Every value is typed once, then each writer picks its own representation. [`Cells`](../reference/Waypoint/Export/Cells.md) does the typing:

| Value | [`CellType`](../reference/Waypoint/Export/CellType.md) |
|---|---|
| `null` | `Empty` |
| `bool` | `Bool` |
| `int`, `float` | `Number` |
| `DateTimeInterface` | `Date` |
| backed enum | typed by its value (`OrderStatus::PickedUp` → `'picked_up'`, Text) |
| `string`, `Stringable` | `Text`; numeric strings such as `'007'` stay text |
| array, other object | `Text` holding its JSON (`["a",1]`) |

What the writers make of them:

| Type | CSV, HTML | XLSX | ODS | JSON Lines | XML |
|---|---|---|---|---|---|
| Number | `1234,5` (decimal separator) | number | float with text `1234,5` | `1234.5` | `1234.5` |
| Bool | `1` / `0` (`booleans`) | boolean | boolean with text `1` / `0` | `true` / `false` | `true` / `false` |
| Date | `dateFormat` (`16.10.2026 18:42`) | serial date with `spreadsheetDateFormat` | date with text in `dateFormat` | ISO 8601 with offset | ISO 8601 with offset |
| Empty | empty | no cell | empty cell | `null` | `xsi:nil="true"` |
| Text | the string | inline string | string | string | escaped text |

The settings in parentheses are [`ExportOptions`](configuration.md). One sheet with every type, built on the bakery's `Order` objects from the [start page](../index.md): the status as the `OrderStatus` enum itself, a boolean, and a `note` column that `Order` has no property for, so its cells are empty:

```php
$customer = fn (Order $o) => $o->customerName;
$total = fn (Order $o) => $o->totalCents() / 100;
$pickedUp = fn (Order $o) => $o->status === OrderStatus::PickedUp;
$placed = fn (Order $o) => new DateTimeImmutable($o->createdAt);
return Sheet::file("orders.$format")
    ->title('Orders')
    ->column('code', 'Order')
    ->column('customer', 'Customer', $customer)
    ->column('status', 'Status')
    ->column('total', 'Total (€)', $total)
    ->column('pickedUp', 'Picked up', $pickedUp)
    ->column('created', 'Placed', $placed)
    ->column('note', 'Note')
    ->rows($orders);
```

The second customer typed a formula into the checkout form as their name.

## Numbers

Integers are written as they are. Floats are written with at most six decimals, without trailing zeros and never in exponent notation: `3.0` is `3`, `0.1234567` is `0.123457`, `1e20` is `100000000000000000000`. The text formats then replace the `.` with `decimalSeparator`. JSON Lines is the exception: it writes the float as PHP's `json_encode()` does, so all its digits survive.

## CSV

```text
Order;Customer;Status;Total (€);Picked up;Placed;Note
3f9a1c07be;Lena;received;8,6;0;16.10.2026 18:42;
81d2e4a90c;"'=HYPERLINK(""http://evil.test"")";picked_up;5,2;1;16.10.2026 20:05;
```

- The first line holds the labels.
- A cell containing the delimiter, a `"` or a line break is quoted, with `"` doubled.
- The file is encoded in `csv->encoding`, Windows-1252 by default; a UTF-8 file starts with a byte order mark. Characters Windows-1252 cannot hold (`✓`, `東京`) become `?`; `€` and the German umlauts are fine.
- A text cell starting with `=`, `+`, `-` or `@` gets a leading `'`, unless it is a number like `-12.5` or `+1234`. A spreadsheet program would otherwise run `=HYPERLINK(...)` from a customer's input as a formula. `csv->guardFormulas = false` turns this off.

## JSON Lines

```text
{"code":"3f9a1c07be","customer":"Lena","status":"received","total":8.6,"pickedUp":false,"created":"2026-10-16T18:42:00+02:00","note":null}
{"code":"81d2e4a90c","customer":"=HYPERLINK(\"http://evil.test\")","status":"picked_up","total":5.2,"pickedUp":true,"created":"2026-10-16T20:05:00+02:00","note":null}
```

One JSON object per row and line, keyed by the column's `name` (default: its key), with JSON's own types. There is no header line. Unicode and slashes are written unescaped.

## HTML

A complete page, `<!DOCTYPE html>` with a small inline stylesheet, a `<title>` and one table:

```html
<table class="wp-sheet"><caption>Orders</caption><thead><tr><th scope="col">Order</th>…</tr></thead><tbody>
<tr><td>3f9a1c07be</td><td>Lena</td><td>received</td><td class="wp-sheet-num">8,6</td><td>0</td><td>16.10.2026 18:42</td><td></td></tr>
```

Cells hold the same text as in CSV, HTML-escaped; number cells carry the class `wp-sheet-num` and are right-aligned. Formulas need no guard here. The same table without the page around it goes into views with [`$this->sheet()`](views.md).

## XML

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Orders xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <row><code>3f9a1c07be</code><customer>Lena</customer><status>received</status><total>8.6</total><pickedUp>false</pickedUp><created>2026-10-16T18:42:00+02:00</created><note xsi:nil="true"/></row>
```

The title is the root element, each row a `<row>` (`xml->rowElement`), each column an element named by its `name`. Values are locale-free. Element names are made valid: anything but letters, digits, `_`, `-` and `.` becomes `_`, and a name not starting with a letter or `_` gets a leading `_` (`Orders 2026` → `Orders_2026`, `2nd` → `_2nd`). Namespaces and schemas: [XML with a schema](xml-schema.md).

## XLSX

A workbook with one sheet, named after the title. Text is stored as inline strings, so a cell starting with `=` stays text and needs no guard. Numbers and booleans are real values, and a date is a serial number with the number format `spreadsheetDateFormat`, so the spreadsheet can sort and calculate with it. The serial uses the date's own wall-clock time: `2026-10-16 18:42+02:00` shows as `16.10.2026 18:42`. The header row is bold, each column is as wide as its label plus two characters (at least 10), and empty cells are left out.

## ODS

An OpenDocument spreadsheet with one table, named after the title. Cells carry their type (`float`, `boolean`, `date`, `string`) and value, plus the text representation for display. Dates are written as local date-time without offset. The `mimetype` entry is stored first and uncompressed, as the format requires.

## The response

Every download gets

```http
Content-Type: <the writer's type>
Content-Disposition: attachment; filename="orders.xlsx"; filename*=UTF-8''orders.xlsx
Cache-Control: no-store
```

`no-store` keeps exported data out of shared caches. A route that returns `Sheet` is listed in the OpenAPI spec with one response content type per installed writer: the six above plus any a [format plugin](custom-formats.md) adds.

See also: [Configuration](configuration.md), [`Cells`](../reference/Waypoint/Export/Cells.md), [the writer classes](../reference/Waypoint/Export/Writers/index.md).
