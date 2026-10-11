# waypoint-export

Table downloads for [Waypoint](https://github.com/Systonia/Waypoint). Return a `Sheet` from a route
and the browser gets a file in the format its extension names: CSV, JSON Lines, HTML, XML with schema,
XLSX or ODS, all written without external libraries and streamed row by row. The OpenAPI spec lists
every installed content type for such a route. Fixed-width record files come from the
[waypoint-export-fixed](../Waypoint-Export-Fixed) sub-plugin; other formats can plug in the same way.

## Setup

```php
$app->plugin(new Waypoint\Export\ExportPlugin());   // before attach()
```

## Usage

```php
use Waypoint\Export\Sheet;

#[Get('/admin/users.xlsx')]
#[Role('admin')]
public function exportUsers(): Sheet
{
    return Sheet::file('users.xlsx')
        ->title('Users')
        ->columns(['id' => 'ID', 'email' => 'Email', 'role' => 'Role'])
        ->column('name', 'Name', fn(User $u): ?string => $u->displayHandle())
        ->rows($this->users->all());
}
```

- **Format** comes from the extension (`users.csv`, `.jsonl`, `.html`, `.xml`, `.xlsx`, `.ods`);
  `Sheet::file('x.dat', format: 'fixed')` names it explicitly. An unknown format fails with the list of
  installed ones.
- **Rows** are arrays or objects; a column reads the key or public property of the same name, or the
  closure you give it. `rows()` accepts any iterable, so a generator over a PDO cursor streams without
  holding the table in memory (XLSX and ODS spool to a temp file, then stream the zip).
- **Row classes:** `columnsOf(RowClass::class)` collects `#[Column('Label', name: 'jsonKey', type: CellType::Number)]`
  properties in declaration order; other attributes on such a property become column meta for writers
  like `#[Fixed]`.
- **`title()`** names the sheet in XLSX/ODS, the caption in HTML and the root element in XML.
- **Inline table:** `<?= $this->sheet($sheet) ?>` in a view renders the same definition as an HTML table.

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
Sheet::file('users.xml')->title('users')->schema('urn:example:users', 'users.xsd')->columnsOf(UserRow::class)->rows($rows);
```

Writes `<users xmlns="urn:example:users" xsi:schemaLocation="urn:example:users users.xsd"><row>…</row></users>`.
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
declare `requires(): [ExportPlugin::class]`, and in `boot()` call `$container->get(Writers::class)->add($writer)`.
`Cells` from the container types the values for you. `waypoint-export-fixed` is the reference.

## Development

```bash
composer install && composer test && composer stan
```

## License

MIT or Apache-2.0, at your option.
