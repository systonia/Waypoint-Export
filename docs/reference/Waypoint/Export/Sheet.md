---
title: "Sheet"
description: "A downloadable table."
order: 100
generated: true
---

`Waypoint\Export\Sheet` · final class · namespace [`Waypoint\Export`](index.md)

```php
final class Sheet
```

A downloadable table. Build it with the fluent API and return it from a
route; ExportPlugin picks the Writer by the file's extension (or $format).

```php
return Sheet::file('users.xlsx')
    ->columns(['id' => 'ID', 'email' => 'Email'])
    ->column('name', 'Name', fn(User $u): string => $u->displayName())
    ->rows($this->users->all());
```

A row is an array or an object; a column reads the key or public property
of the same name unless it has its own value closure.

## Methods

### file()

```php
public static function file(string $filename, ?string $format = null): \Waypoint\Export\Sheet
```

A sheet that downloads as $filename.

**Parameters**

- `$filename` `string` — The download's file name without a path: no `/`, `\`, `:`, `*`, `?`, `"`, `<`, `>`, `|` or control characters.
- `$format` `string|null` — Writer name; default: the file's extension, lowercased (`users.xlsx` -> xlsx).

**Returns** [`Sheet`](Sheet.md)

**Throws**

- [`InvalidArgumentException`](https://www.php.net/manual/en/class.invalidargumentexception.php) — when the file name is not plain, or the format (or extension) is not lowercase letters and digits

### csv()

```php
public static function csv(string $filename): \Waypoint\Export\Sheet
```

A sheet written as CSV whatever the file's extension.

**Parameters**

- `$filename` `string`

**Returns** [`Sheet`](Sheet.md)

**Throws**

- [`InvalidArgumentException`](https://www.php.net/manual/en/class.invalidargumentexception.php) — when the file name is not plain

### title()

```php
public function title(string $title): \Waypoint\Export\Sheet
```

Sheet name in XLSX/ODS, caption in HTML, root element in XML. Default: the file name without extension.

**Parameters**

- `$title` `string`

**Returns** [`Sheet`](Sheet.md)

### schema()

```php
public function schema(string $namespace, ?string $location = null, ?string $xsd = null): \Waypoint\Export\Sheet
```

XML only: the document's namespace and optional xsi:schemaLocation. $xsd is a schema file to
validate against (ExportOptions::$xml->validate); without it the writer's own generated XSD is used.

**Parameters**

- `$namespace` `string`
- `$location` `?string`
- `$xsd` `?string`

**Returns** [`Sheet`](Sheet.md)

### columns()

```php
public function columns(array $columns): \Waypoint\Export\Sheet
```

Adds a column per entry, each reading the row's key or property of the same name.

**Parameters**

- `$columns` `array<string, string>` — key => header label, in output order.

**Returns** [`Sheet`](Sheet.md)

### column()

```php
public function column(
    string $key,
    string $label,
    ?\Closure $value = null,
    ?string $name = null,
    ?\Waypoint\Export\CellType $type = null,
): \Waypoint\Export\Sheet
```

Adds one column after the ones already defined.

**Parameters**

- `$key` `string` — The row's array key or public property to read.
- `$label` `string` — Header text in CSV, HTML, XLSX and ODS.
- `$value` `(`[`Closure`](https://www.php.net/manual/en/class.closure.php)`(mixed): mixed)|null` — Computes the cell from the row; null reads the row's key/property.
- `$name` `string|null` — JSON key / XML element name; default $key.
- `$type` [`CellType`](CellType.md)`|null` — Declared type for the generated XSD; null types each value as it comes.

**Returns** [`Sheet`](Sheet.md)

### meta()

```php
public function meta(object $meta): \Waypoint\Export\Sheet
```

Attaches a writer-specific attribute object (e.g. #[Fixed]) to the column added last, keyed by its class.

**Parameters**

- `$meta` `object`

**Returns** [`Sheet`](Sheet.md)

**Throws**

- [`InvalidArgumentException`](https://www.php.net/manual/en/class.invalidargumentexception.php) — when no column has been added yet

### columnsOf()

```php
public function columnsOf(string $class): \Waypoint\Export\Sheet
```

Columns from the #[Column] properties of a row class, in declaration order; every other
attribute on such a property (e.g. #[Fixed]) becomes column meta.

**Parameters**

- `$class` `class-string`

**Returns** [`Sheet`](Sheet.md)

### rows()

```php
public function rows(iterable $rows): \Waypoint\Export\Sheet
```

The rows to write, iterated when the file is written.

**Parameters**

- `$rows` `iterable<mixed>` — Arrays or objects; a generator streams without holding every row.

**Returns** [`Sheet`](Sheet.md)

### getFilename()

```php
public function getFilename(): string
```

The download's file name as given to file().

**Returns** `string`

### getFormat()

```php
public function getFormat(): string
```

The writer name: the explicit format, else the lowercased extension.

**Returns** `string`

### getTitle()

```php
public function getTitle(): string
```

The title given to title(), else the file name without its extension.

**Returns** `string`

### getSchema()

```php
public function getSchema(): ?array
```

What schema() set, or null.

**Returns** `array{namespace: string, location: string|null, xsd: string|null}|null`

### getColumns()

```php
public function getColumns(): array
```

The columns in output order, each as `{key, label, name, type, value, meta}`.

**Returns** `list<ColumnDef>`

### getRows()

```php
public function getRows(): iterable
```

The iterable given to rows(); empty by default.

**Returns** `iterable<mixed>`
