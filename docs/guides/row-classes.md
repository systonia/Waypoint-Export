---
title: Row classes
description: "Declare the columns once with #[Column] on a class and reuse them with columnsOf()."
order: 20
---

When the same export appears in several places, or the column list is long, the columns can live on a class: each public property with [`#[Column]`](../reference/Waypoint/Export/Column.md) is a column, and `Sheet::columnsOf()` collects them in declaration order.

```php
use Waypoint\Export\{CellType, Column};

final class CustomerRow
{
    #[Column(
        'Customer no.',
        name: 'customerNo',
        type: CellType::Number
    )]
    public int $id;

    #[Column('Email')]
    public string $email;

    #[Column(
        'Balance',
        type: CellType::Number
    )]
    public float $balance;

    #[Column(
        'Since',
        name: 'registered',
        type: CellType::Date
    )]
    public DateTimeImmutable $since;

    public string $passwordHash;   // no #[Column]: never exported

    public static function of(User $user): self
    {
        $row = new self();
        $row->id = $user->id;
        $row->email = $user->email;
        $row->balance = $user->balance;
        $row->since = $user->registered;
        return $row;
    }
}
```

```php
#[Get('/customers.jsonl')]
public function customers(): Sheet
{
    return Sheet::file('customers.jsonl')
        ->columnsOf(CustomerRow::class)
        ->rows(array_map(CustomerRow::of(...), $this->users->all()));
}
```

```text
{"customerNo":1,"email":"ada@example.com","balance":1234.5,"registered":"2026-03-01T09:15:00+00:00"}
{"customerNo":2,"email":"grace@example.com","balance":-20,"registered":"2026-05-17T14:00:00+00:00"}
```

`#[Column]` takes the same three settings as `Sheet::column()`:

| Argument | Meaning |
|---|---|
| `label` | the header text |
| `name` | the JSON key and XML element name; default: the property name |
| `type` | a `CellType` for the generated XSD; default: typed per value |

The column's key is the property name, so the rows are read by property: they can be instances of the row class, other objects with the same public properties, or arrays with those keys. `columnsOf()` adds to columns already defined, and `column()` calls after it add more.

## Column meta

Every other attribute on a `#[Column]` property travels with the column as *meta*, for writers that need more than a label. The fixed-width writer of [waypoint-export-fixed](https://github.com/systonia/Waypoint-Export-Fixed) reads its `#[Fixed]` layout this way:

```php
use Waypoint\Export\Column;
use Waypoint\Export\Fixed\Fixed;

final class Satz
{
    #[Column('Kundennummer')]
    #[Fixed(
        8,
        align: 'right',
        pad: '0'
    )]
    public int $customer;
}
```

Without a row class, `meta()` attaches such an object to the column added last:

```php
Sheet::file('lieferung.txt')
    ->column('customer', 'Kundennummer')
    ->meta(new Fixed(8, align: 'right', pad: '0'));
```

Meta is stored by the object's class, `$column['meta'][Fixed::class]`; the built-in writers ignore it. `meta()` before any column throws an `InvalidArgumentException`. How a writer reads meta is shown in [Adding a format](custom-formats.md#column-meta).

See also: [Building a sheet](sheets.md), [`Column`](../reference/Waypoint/Export/Column.md).
