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
| backed enum | typed by its value (`Status::Active` → `'active'`, Text) |
| `string`, `Stringable` | `Text` — numeric strings such as `'007'` stay text |
| array, other object | `Text` holding its JSON (`["a",1]`) |

What the writers make of them:

| Type | CSV, HTML | XLSX | ODS | JSON Lines | XML |
|---|---|---|---|---|---|
| Number | `1234,5` (decimal separator) | number | float with text `1234,5` | `1234.5` | `1234.5` |
| Bool | `1` / `0` (`booleans`) | boolean | boolean with text `1` / `0` | `true` / `false` | `true` / `false` |
| Date | `dateFormat` (`16.09.2026 21:30`) | serial date with `spreadsheetDateFormat` | date with text in `dateFormat` | ISO 8601 with offset | ISO 8601 with offset |
| Empty | empty | no cell | empty cell | `null` | `xsi:nil="true"` |
| Text | the string | inline string | string | string | escaped text |

The settings in parentheses are [`ExportOptions`](configuration.md). One sheet with every type, `/admin/users.{format}` from the [start page](../index.md) plus a status enum and a nullable note:

```php
Sheet::file("users.$format")
    ->title('Users')
    ->columns(['id' => 'ID', 'email' => 'Email'])
    ->column('name', 'Name', fn(User $u): string => $u->fullName())
    ->column('status', 'Status')
    ->columns(['balance' => 'Balance', 'newsletter' => 'Newsletter', 'registered' => 'Registered', 'note' => 'Note'])
    ->rows($this->users->all());
```

## Numbers

Integers are written as they are. Floats are written with at most six decimals, without trailing zeros and never in exponent notation: `3.0` is `3`, `0.1234567` is `0.123457`, `1e20` is `100000000000000000000`. The text formats then replace the `.` with `decimalSeparator`. JSON Lines is the exception: it writes the float as PHP's `json_encode()` does, so all its digits survive.

## CSV

```text
ID;Email;Name;Status;Balance;Newsletter;Registered;Note
1;ada@example.com;Ada Lovelace;active;1234,5;1;01.03.2026 09:15;
2;grace@example.com;Grace Hopper;blocked;-20;0;17.05.2026 14:00;"'=HYPERLINK(""http://evil"")"
```

- The first line holds the labels.
- A cell containing the delimiter, a `"` or a line break is quoted, with `"` doubled.
- The file is encoded in `csv->encoding`, Windows-1252 by default; a UTF-8 file starts with a byte order mark. Characters Windows-1252 cannot hold (`✓`, `東京`) become `?`; `€` and the German umlauts are fine.
- A text cell starting with `=`, `+`, `-` or `@` gets a leading `'`, unless it is a number like `-12.5` or `+1234`. A spreadsheet program would otherwise run `=HYPERLINK(...)` from a user's input as a formula. `csv->guardFormulas = false` turns this off.

## JSON Lines

```text
{"id":1,"email":"ada@example.com","name":"Ada Lovelace","status":"active","balance":1234.5,"newsletter":true,"registered":"2026-03-01T09:15:00+00:00","note":null}
{"id":2,"email":"grace@example.com","name":"Grace Hopper","status":"blocked","balance":-20,"newsletter":false,"registered":"2026-05-17T14:00:00+00:00","note":"=HYPERLINK(\"http://evil\")"}
```

One JSON object per row and line, keyed by the column's `name` (default: its key), with JSON's own types. There is no header line. Unicode and slashes are written unescaped.

## HTML

A complete page, `<!DOCTYPE html>` with a small inline stylesheet, a `<title>` and one table:

```html
<table class="wp-sheet"><caption>Users</caption><thead><tr><th scope="col">ID</th>…</tr></thead><tbody>
<tr><td class="wp-sheet-num">1</td><td>ada@example.com</td><td>Ada Lovelace</td><td>active</td><td class="wp-sheet-num">1234,5</td><td>1</td><td>01.03.2026 09:15</td><td></td></tr>
```

Cells hold the same text as in CSV, HTML-escaped; number cells carry the class `wp-sheet-num` and are right-aligned. Formulas need no guard here. The same table without the page around it goes into views with [`$this->sheet()`](views.md).

## XML

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Users xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <row><id>1</id><email>ada@example.com</email><name>Ada Lovelace</name><status>active</status><balance>1234.5</balance><newsletter>true</newsletter><registered>2026-03-01T09:15:00+00:00</registered><note xsi:nil="true"/></row>
```

The title is the root element, each row a `<row>` (`xml->rowElement`), each column an element named by its `name`. Values are locale-free. Element names are made valid: anything but letters, digits, `_`, `-` and `.` becomes `_`, and a name not starting with a letter or `_` gets a leading `_` (`Kunden 2026` → `Kunden_2026`, `2nd` → `_2nd`). Namespaces and schemas: [XML with a schema](xml-schema.md).

## XLSX

A workbook with one sheet, named after the title. Text is stored as inline strings, so a cell starting with `=` stays text and needs no guard. Numbers and booleans are real values, and a date is a serial number with the number format `spreadsheetDateFormat`, so the spreadsheet can sort and calculate with it. The serial uses the date's own wall-clock time: `2026-09-16 21:30+02:00` shows as `16.09.2026 21:30`. The header row is bold, each column is as wide as its label plus two characters (at least 10), and empty cells are left out.

## ODS

An OpenDocument spreadsheet with one table, named after the title. Cells carry their type (`float`, `boolean`, `date`, `string`) and value, plus the text representation for display. Dates are written as local date-time without offset. The `mimetype` entry is stored first and uncompressed, as the format requires.

## The response

Every download gets

```http
Content-Type: <the writer's type>
Content-Disposition: attachment; filename="users.xlsx"; filename*=UTF-8''users.xlsx
Cache-Control: no-store
```

`no-store` keeps exported data out of shared caches. A route that returns `Sheet` is listed in the OpenAPI spec with one response content type per installed writer — the six above plus any a [format plugin](custom-formats.md) adds.

See also: [Configuration](configuration.md), [`Cells`](../reference/Waypoint/Export/Cells.md), [the writer classes](../reference/Waypoint/Export/Writers/index.md).
