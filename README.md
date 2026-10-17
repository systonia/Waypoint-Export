# waypoint-export

Table downloads for [Waypoint](https://github.com/Systonia/Waypoint). Return a `Sheet` from a route
and the browser gets a file in the format its extension names: CSV, JSON Lines, HTML, XML with schema,
XLSX or ODS, all written row by row without external libraries. The OpenAPI spec lists
every installed content type for such a route. Fixed-width record files come from the
[waypoint-export-fixed](https://github.com/systonia/Waypoint-Export-Fixed) sub-plugin; other formats can plug in the same way.

Documentation: [guides and reference](docs/index.md), starting with [building a sheet](docs/guides/sheets.md).

## Setup

```php
$app->plugin(new Waypoint\Export\ExportPlugin());   // before attach()
```

## Usage

The orders export of the Sonnenkorn bakery, shortened from
[`BackofficeController`](https://github.com/systonia/Waypoint-Bakery/blob/main/src/Controllers/BackofficeController.php):

```php
use Waypoint\Export\Sheet;

#[Get('/orders.{format}')]
#[Role('owner')]
public function export(string $format): Sheet
{
    if (!in_array($format, ['csv', 'xlsx'], true)) {
        throw new NotFoundException("No export as $format");
    }
    $total = fn (Order $o) => $o->totalCents() / 100;
    return Sheet::file("orders.$format")
        ->title('Orders')
        ->columns(['code' => 'Order', 'customerName' => 'Customer', 'email' => 'Email'])
        ->column('total', 'Total (€)', $total)
        ->rows($this->orders->recent(90));
}
```

- **Format** comes from the extension (`orders.csv`, `.jsonl`, `.html`, `.xml`, `.xlsx`, `.ods`);
  `Sheet::file('till.dat', format: 'fixed')` names it explicitly. An unknown format fails with the list of
  installed ones.
- **Rows** are arrays or objects; a column reads the key or public property of the same name, or the
  closure you give it. `rows()` accepts any iterable, so a generator that pages through a table is read one row
  at a time (XLSX and ODS spool to a temp file, then zip it). The finished file is collected in the
  response body and held in memory once before it is sent; see [memory](docs/guides/edge-cases.md#memory).
- **Row classes:** `columnsOf(RowClass::class)` collects `#[Column('Label', name: 'jsonKey', type: CellType::Number)]`
  properties in declaration order; other attributes on such a property become column meta for writers
  like `#[Fixed]`.
- **`title()`** names the sheet in XLSX/ODS, the caption in HTML and the root element in XML.
- **Inline table:** `{{ $this->sheet($sheet) }}` in a view renders the same definition as an HTML table.

### Cell types

Every value is typed once (`Cells`) and each writer chooses the representation:

| Value | CSV / HTML / fixed | XLSX / ODS | JSONL | XML |
|---|---|---|---|---|
| int, float | `1234,5` (decimal separator) | number | `1234.5` | `1234.5` |
| bool | `1` / `0` (`booleans`) | boolean | `true` | `true` |
| DateTimeInterface | `dateFormat` | date with number format | ISO 8601 | ISO 8601 |
| null | empty | empty | `null` | `xsi:nil="true"` |
| backed enum | its value | | | |
| array, object | JSON text | | | |

CSV cells starting with `=`, `+`, `-` or `@` get a leading `'` unless they are numbers, so a pasted
`=HYPERLINK(...)` never runs in the recipient's spreadsheet. XLSX and ODS store text as text, which
needs no guard.

### XML with schema

```php
Sheet::file('sales.xml')->title('sales')->schema('urn:sonnenkorn:sales', 'sales.xsd')->columnsOf(SalesRow::class)->rows($rows);
```

Writes `<sales xmlns="urn:sonnenkorn:sales" xsi:schemaLocation="urn:sonnenkorn:sales sales.xsd"><row>…</row></sales>`.
`XmlWriter::xsd($sheet)` derives the matching schema from the columns (declared `type`s, else `xs:string`)
so you can serve it next to the data. With `ExportOptions::$xml->validate = true` every document is
checked against the XSD (`schema(..., xsd: '/path/to/given.xsd')` for one you were handed) before it
leaves; for dev and CI.

## Options (`ExportOptions`)

| Setting | Default | Description |
|---|---|---|
| `dateFormat` | `'d.m.Y H:i'` | Dates in the text formats. |
| `spreadsheetDateFormat` | `'dd.mm.yyyy hh:mm'` | Excel number format for date cells. |
| `decimalSeparator` | `','` | Floats in the text formats. |
| `booleans` | `['1', '0']` | Boolean words in the text formats. |
| `csv->delimiter` | `';'` | Excel's list separator in most of Europe; `','` for US locales. |
| `csv->encoding` | `'Windows-1252'` | Or `'UTF-8'`, written with a BOM so Excel detects it. |
| `csv->lineEnding` | `"\r\n"` | What Excel writes itself. |
| `csv->guardFormulas` | `true` | Neutralise formula-looking cells. |
| `xml->validate` | `false` | Validate every XML export against its XSD. |
| `xml->rowElement` | `'row'` | Element name of one row. |

## Adding a format from another plugin

Implement `Waypoint\Export\Writer` (`extension()`, `contentType()`, `stream(Sheet): iterable<string>`),
declare `requires(): [ExportPlugin::class]`, and take `Cells` and `Writers` as `boot()` parameters:

```php
public function boot(Cells $cells, Writers $writers): void
{
    $writers->add(new MarkdownWriter($cells));   // Cells types the values for the writer
}
```

[Adding a format](docs/guides/custom-formats.md) walks through it; `waypoint-export-fixed` is the reference.

## Development

```bash
composer install && composer test && composer stan
```

## License

MIT or Apache-2.0, at your option.
