---
title: Row classes
description: "Declare the columns once with #[Column] on a class and reuse them with columnsOf()."
order: 20
---

When the same export appears in several places, or the column list is long, the columns can live on a class: each public property with [`#[Column]`](../reference/Waypoint/Export/Column.md) is a column, and `Sheet::columnsOf()` collects them in declaration order.

A row for the bakery's daily sales, one product on one day, as `daily_sales` holds them:

```php
use DateTimeImmutable;
use Waypoint\Export\{CellType, Column};
use Waypoint\Support\Row;

final class SalesRow
{
    #[Column(
        'Day',
        type: CellType::Date
    )]
    public DateTimeImmutable $day;

    #[Column('Product')]
    public string $product;

    #[Column(
        'Sold',
        name: 'quantity',
        type: CellType::Number
    )]
    public int $sold;

    #[Column(
        'Revenue (€)',
        name: 'revenue',
        type: CellType::Number
    )]
    public float $revenue;

    public int $productId;   // no #[Column]: never exported

    public static function of(Row $row): self
    {
        $sales = new self();
        $sales->day = new DateTimeImmutable($row->string('day'));
        $sales->product = $row->string('product');
        $sales->sold = $row->int('quantity');
        $sales->revenue = $row->float('revenue');
        $sales->productId = $row->int('product_id');
        return $sales;
    }
}
```

```php
#[Get('/sales.jsonl')]
#[Role('owner')]
public function salesLines(): Sheet
{
    $rows = $this->db->query(<<<'SQL'
        SELECT s.day::text AS day, p.id AS product_id, p.name ->> 'en' AS product, s.quantity, s.revenue_cents / 100.0 AS revenue
        FROM daily_sales s JOIN products p ON p.id = s.product_id
        ORDER BY s.day, p.position
        SQL);
    return Sheet::file('sales.jsonl')
        ->columnsOf(SalesRow::class)
        ->rows(array_map(fn (array $values) => SalesRow::of(new Row($values)), $rows));
}
```

```text
{"day":"2026-10-16T00:00:00+02:00","product":"Lye pretzel","quantity":48,"revenue":57.6}
{"day":"2026-10-16T00:00:00+02:00","product":"Butter croissant","quantity":31,"revenue":55.8}
```

`#[Column]` takes the same three settings as `Sheet::column()`:

| Argument | Meaning |
|---|---|
| `label` | the header text |
| `name` | the JSON key and XML element name; default: the property name |
| `type` | a `CellType` for the generated XSD; default: typed per value |

The column's key is the property name, so the rows are read by property: they can be instances of the row class, other objects with the same public properties, or arrays with those keys. `columnsOf()` adds to columns already defined, and `column()` calls after it add more.

## Column meta

Every other attribute on a `#[Column]` property travels with the column as *meta*, for writers that need more than a label. The fixed-width writer of [waypoint-export-fixed](https://github.com/systonia/Waypoint-Export-Fixed) reads its `#[Fixed]` layout this way, here for a price list the shop's till reads:

```php
use Waypoint\Export\Column;
use Waypoint\Export\Fixed\Fixed;

final class TillItem
{
    #[Column('Price (cents)')]
    #[Fixed(
        6,
        align: 'right',
        pad: '0'
    )]
    public int $priceCents;
}
```

Without a row class, `meta()` attaches such an object to the column added last:

```php
Sheet::file('till.txt')
    ->column('priceCents', 'Price (cents)')
    ->meta(new Fixed(6, align: 'right', pad: '0'));
```

Meta is stored by the object's class, `$column['meta'][Fixed::class]`; the built-in writers ignore it. `meta()` before any column throws an `InvalidArgumentException`. How a writer reads meta is shown in [Adding a format](custom-formats.md#column-meta).

See also: [Building a sheet](sheets.md), [`Column`](../reference/Waypoint/Export/Column.md).
