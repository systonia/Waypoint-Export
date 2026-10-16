---
title: Building a sheet
description: File name and format, columns, computed cells, rows from arrays, objects and generators.
order: 10
---

A [`Sheet`](../reference/Waypoint/Export/Sheet.md) describes one download: its file name, its columns in order, and the rows. A route returns it, and the plugin writes it with the writer for its format. Every method returns the sheet, so it is built in one expression.

## File name and format

```php
Sheet::file('users.xlsx');               // format xlsx, from the extension
Sheet::file('Kunden 2026.XLSX');         // format xlsx: the extension is lowercased
Sheet::file('lieferung.txt', 'fixed');   // format named explicitly
Sheet::csv('users.txt');                 // CSV whatever the extension says
```

The file name is what the browser saves, sent in `Content-Disposition` both plain and as `filename*=UTF-8''…` for names with non-ASCII characters. It must be a plain name: `/`, `\`, `:`, `*`, `?`, `"`, `<`, `>`, `|` and control characters throw an `InvalidArgumentException`, so a name built from request input cannot carry a path. A file name without an extension needs an explicit format, and a format is lowercase letters and digits (`'csv'`, not `'CSV'`).

A route can take the format from the path, so one method serves every format:

```php
#[Get('/orders.{format}')]
public function orders(string $format): Sheet
{
    return Sheet::file("orders.$format")
        ->columns(['id' => 'Order', 'customer' => 'Customer', 'total' => 'Total'])
        ->rows($this->orders->all());
}
```

## Columns

`columns()` adds one column per entry, `key => header label`, in that order. A column reads the row's array key or public property named by its key; a missing key or property is an empty cell.

```php
Sheet::file('users.csv')
    ->columns(['id' => 'ID', 'email' => 'Email', 'role' => 'Role']);
```

`column()` adds one column with more control:

```php
->column('name', 'Name', fn(User $u): string => $u->fullName())
->column('total', 'Total', fn(array $r): float => (float) $r['total'], type: CellType::Number)
->column('email', 'Email', name: 'emailAddress')
```

| Argument | Meaning |
|---|---|
| `$key` | the array key or property to read |
| `$label` | the header text in CSV, HTML, XLSX and ODS |
| `$value` | a closure that gets the row and returns the cell's value; replaces reading `$key` |
| `$name` | the JSON key in JSON Lines and the element name in XML; default: `$key` |
| `$type` | a [`CellType`](../reference/Waypoint/Export/CellType.md) for the generated XSD; values are still typed one by one |

Calls can be mixed; the columns appear in the order they were added. A closure is the place for anything derived: a formatted name, a value from a related object, a conversion from a database string.

The value a column produces decides how it is written: an `int` or `float` becomes a number, a `bool` a boolean, a `DateTimeInterface` a date, a backed enum its value, `null` an empty cell, and a string stays text, also `'007'` or `'0049 30'`, which keep their leading zeros. [Formats and cell types](formats.md) shows each format's representation.

## Rows

`rows()` takes any iterable of rows; a row is an array or an object.

```php
->rows($this->users->all())                     // a list of objects
->rows([['month' => '2026-09', 'net' => 18250.75]])   // arrays
```

A generator reads a large table without holding it in memory. With PDO:

```php
#[Get('/orders.csv')]
public function orders(): Sheet
{
    return Sheet::file('orders.csv')
        ->columns(['id' => 'Order', 'customer' => 'Customer'])
        ->column('total', 'Total', fn(array $r): float => (float) $r['total'])
        ->column('placed', 'Placed', fn(array $r): DateTimeImmutable => new DateTimeImmutable($r['placed']))
        ->rows($this->orderRows());
}

/** @return iterable<array<string, mixed>> */
private function orderRows(): iterable
{
    $statement = $this->pdo->query('SELECT id, customer, total, placed FROM orders ORDER BY id');
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        yield $row;
    }
}
```

```text
Order;Customer;Total;Placed
1;Ada;99,9;01.10.2026 10:00
2;Grace;15;02.10.2026 11:30
```

The closures turn the database's strings into a `float` and a date, so XLSX and ODS get real numbers and dates instead of text. The rows are iterated once, when the file is written, so a generator can be passed to only one sheet that is written once.

A sheet without rows is still a valid file: the header line in CSV, the header row in XLSX and ODS, an empty root element in XML, nothing in JSON Lines.

## Title

```php
Sheet::file('users.xlsx')->title('Users');
```

The title is the sheet name in XLSX and ODS, the `<caption>` and `<title>` in HTML, and the root element in XML. Without `title()` it is the file name without its extension (`users`). XLSX cuts it to 31 characters and replaces `\ / ? * [ ] :` with `_`; XML replaces every character that is not a letter, digit, `_`, `-` or `.` with `_`.

## Who may download

A sheet is returned from an ordinary route, so the route's guards apply as usual:

```php
#[Get('/admin/users.xlsx')]
#[Role('admin')]
public function exportUsers(): Sheet
{
    return Sheet::file('users.xlsx')->columns(['id' => 'ID', 'email' => 'Email'])->rows($this->users->all());
}
```

Columns are opt-in: only what is named in `columns()`, `column()` or `#[Column]` is written, so a password hash on the row object never reaches the file.

See also: [Row classes](row-classes.md), [`Sheet`](../reference/Waypoint/Export/Sheet.md).
