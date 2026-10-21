---
title: Building a sheet
description: File name and format, columns, computed cells, rows from arrays, objects and generators.
order: 10
---

A [`Sheet`](../reference/Waypoint/Export/Sheet.md) describes one download: its file name, its columns in order, and the rows. A route returns it, and the plugin writes it with the writer for its format. Every method returns the sheet, so it is built in one expression.

## File name and format

```php
Sheet::file('orders.xlsx');                  // format xlsx, from the extension
Sheet::file('Bestellungen Oktober.XLSX');    // format xlsx: the extension is lowercased
Sheet::file('till.txt', 'fixed');            // format named explicitly
Sheet::csv('stock.txt');                     // CSV whatever the extension says
```

The file name is what the browser saves, sent in `Content-Disposition` both plain and as `filename*=UTF-8''…` for names with non-ASCII characters. It must be a plain name: `/`, `\`, `:`, `*`, `?`, `"`, `<`, `>`, `|` and control characters throw an `InvalidArgumentException`, so a name built from request input cannot carry a path. A file name without an extension needs an explicit format, and a format is lowercase letters and digits (`'csv'`, not `'CSV'`).

A route can take the format from the path, so one method serves several formats. The bakery's back office lets two of them through (from `src/Controllers/BackofficeController.php`):

```php
#[Get('/orders.{format}')]
#[Role('owner')]
public function export(string $format): Sheet
{
    if (!in_array($format, ['csv', 'xlsx'], true)) {
        throw new NotFoundException("No export as $format");
    }
    return $this->sheet("orders.$format", $this->orders->recent(90));
}
```

Without the check every installed format would be served, and an unknown one would answer 500; [Errors and limits](edge-cases.md#unknown-formats) shows how to ask the registry instead of keeping a list.

## Columns

`columns()` adds one column per entry, `key => header label`, in that order. A column reads the row's array key or public property named by its key; a missing key or property is an empty cell.

```php
Sheet::file('stock.csv')
    ->columns(['slug' => 'Product', 'category' => 'Category', 'stock' => 'In stock']);
```

`column()` adds one column with more control:

```php
->column('code', 'Order')
->column('total', 'Total (€)', fn (Order $o) => $o->totalCents() / 100, type: CellType::Number)
->column('email', 'Email', name: 'customerEmail')
```

| Argument | Meaning |
|---|---|
| `$key` | the array key or property to read |
| `$label` | the header text in CSV, HTML, XLSX and ODS |
| `$value` | a closure that gets the row and returns the cell's value; replaces reading `$key` |
| `$name` | the JSON key in JSON Lines and the element name in XML; default: `$key` |
| `$type` | a [`CellType`](../reference/Waypoint/Export/CellType.md) for the generated XSD; values are still typed one by one |

Calls can be mixed; the columns appear in the order they were added. A closure is the place for anything derived: a formatted name, a value from a related object, a conversion from a database string.

The bakery's `sheet()` helper builds every column of its orders export this way, with the closures in variables to keep the calls short:

```php
/** @param list<Order> $orders */
private function sheet(string $file, array $orders): Sheet
{
    $placed = fn (Order $o) => new DateTimeImmutable($o->createdAt);
    $pickup = fn (Order $o) => new DateTimeImmutable($o->pickupDate);
    $customer = fn (Order $o) => $o->customerName;
    $status = fn (Order $o) => $o->status->label($this->translator);
    $items = fn (Order $o) => implode(', ', array_map(fn (OrderLine $l) => "$l->quantity × $l->name", $o->lines));
    $total = fn (Order $o) => $o->totalCents() / 100;
    return Sheet::file($file)
        ->title($this->translator->t('Orders'))
        ->column('code', $this->translator->t('Order'))
        ->column('created', $this->translator->t('Placed'), $placed)
        ->column('pickup', $this->translator->t('Pickup'), $pickup)
        ->column('customer', $this->translator->t('Customer'), $customer)
        ->column('email', $this->translator->t('Email'))
        ->column('status', $this->translator->t('Status'), $status)
        ->column('items', $this->translator->t('Items'), $items)
        ->column('total', $this->translator->t('Total (€)'), $total)
        ->rows($orders);
}
```

`column()` takes the type of the closure's parameter as it is written, inline in the call or in a variable: PHPStan sees the `Order` inside the closure either way.

The value a column produces decides how it is written: an `int` or `float` becomes a number, a `bool` a boolean, a `DateTimeInterface` a date, a backed enum its value, `null` an empty cell, and a string stays text, also `'007'` or `'0049 30'`, which keep their leading zeros. [Formats and cell types](formats.md) shows each format's representation.

## Rows

`rows()` takes any iterable of rows; a row is an array or an object.

```php
->rows($this->orders->recent(90))                          // a list of Order objects
->rows([['day' => '2026-10-02', 'revenue' => 412.6]])      // arrays
```

A generator reads a large table without holding it in memory. Every order since the shop opened, 500 rows per query to `Data\Postgres`:

```php
#[Get('/orders-archive.csv')]
#[Role('owner')]
public function archive(): Sheet
{
    $total = fn (array $r): float => (int) $r['total_cents'] / 100;
    $placed = fn (array $r): DateTimeImmutable => new DateTimeImmutable((string) $r['created_at']);
    return Sheet::file('orders-archive.csv')
        ->columns(['code' => 'Order', 'customer_name' => 'Customer'])
        ->column('total', 'Total (€)', $total)
        ->column('placed', 'Placed', $placed)
        ->rows($this->allOrders());
}

/** @return iterable<array<string, mixed>> */
private function allOrders(): iterable
{
    $after = 0;
    do {
        $rows = $this->db->query(
            'SELECT id, code, customer_name, total_cents, created_at FROM orders WHERE id > $1 ORDER BY id LIMIT 500',
            [$after],
        );
        foreach ($rows as $row) {
            yield $row;
            $after = $row['id'];
        }
    } while (count($rows) === 500);
}
```

```text
Order;Customer;Total (€);Placed
3f9a1c07be;Lena;8,6;16.10.2026 18:42
81d2e4a90c;Paul;5,2;16.10.2026 20:05
```

The closures turn the cents into euros and the timestamp into a date, so XLSX and ODS get real numbers and dates instead of text. The rows are iterated once, when the file is written, so a generator can be passed to only one sheet that is written once.

A sheet without rows is still a valid file: the header line in CSV, the header row in XLSX and ODS, an empty root element in XML, nothing in JSON Lines.

## Title

```php
Sheet::file('orders.xlsx')->title('Orders');
```

The title is the sheet name in XLSX and ODS, the `<caption>` and `<title>` in HTML, and the root element in XML. Without `title()` it is the file name without its extension (`orders`). XLSX cuts it to 31 characters and replaces `\ / ? * [ ] :` with `_`; XML replaces every character that is not a letter, digit, `_`, `-` or `.` with `_`.

## Who may download

A sheet is returned from an ordinary route, so the route's guards apply as usual. The bakery's `BackofficeController` carries `#[Authenticated]` for everyone in the bakery, and the export adds `#[Role('owner')]`: the staff get a 403.

```php
#[Controller('/backoffice')]
#[Ignore]
#[Authenticated]
final class BackofficeController
{
    #[Get('/orders.{format}')]
    #[Role('owner')]
    public function export(string $format): Sheet
    {
        // ...
    }
}
```

Columns are opt-in: only what is named in `columns()`, `column()` or `#[Column]` is written, so the order's internal `id` and its `locale` never reach the file.

See also: [Row classes](row-classes.md), [`Sheet`](../reference/Waypoint/Export/Sheet.md).
