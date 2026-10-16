<?php

namespace Waypoint\Export;

use Closure;
use InvalidArgumentException;
use ReflectionClass;

/**
 * A downloadable table. Build it with the fluent API and return it from a
 * route; ExportPlugin picks the Writer by the file's extension (or $format).
 *
 * ```php
 * return Sheet::file('users.xlsx')
 *     ->columns(['id' => 'ID', 'email' => 'Email'])
 *     ->column('name', 'Name', fn(User $u): string => $u->displayName())
 *     ->rows($this->users->all());
 * ```
 *
 * A row is an array or an object; a column reads the key or public property
 * of the same name unless it has its own value closure.
 *
 * @phpstan-type ColumnDef array{key: string, label: string, name: string, type: CellType|null, value: Closure|null, meta: array<class-string, object>}
 */
final class Sheet
{
    /** @var list<ColumnDef> */
    private array $columns = [];

    /** @var iterable<mixed> */
    private iterable $rows = [];

    private ?string $title = null;

    /** @var array{namespace: string, location: string|null, xsd: string|null}|null */
    private ?array $schema = null;

    private function __construct(private readonly string $filename, private readonly string $format)
    {
        if (!preg_match('/^[^\/\\\\:*?"<>|\x00-\x1f]+$/', $filename)) {
            throw new InvalidArgumentException("'$filename' is not a plain file name.");
        }
        if (!preg_match('/^[a-z0-9]+$/', $format)) {
            throw new InvalidArgumentException("'$filename' has no usable extension; pass the format explicitly.");
        }
    }

    /**
     * A sheet that downloads as $filename.
     *
     * @param string $filename The download's file name without a path: no `/`, `\`, `:`, `*`, `?`, `"`, `<`, `>`, `|` or control characters.
     * @param string|null $format Writer name; default: the file's extension, lowercased (`users.xlsx` -> xlsx).
     * @throws InvalidArgumentException when the file name is not plain, or the format (or extension) is not lowercase letters and digits
     */
    public static function file(string $filename, ?string $format = null): self
    {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        return new self($filename, $format ?? $extension);
    }

    /**
     * A sheet written as CSV whatever the file's extension.
     *
     * @throws InvalidArgumentException when the file name is not plain
     */
    public static function csv(string $filename): self
    {
        return self::file($filename, 'csv');
    }

    /** Sheet name in XLSX/ODS, caption in HTML, root element in XML. Default: the file name without extension. */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * XML only: the document's namespace and optional xsi:schemaLocation. $xsd is a schema file to
     * validate against (ExportOptions::$xml->validate); without it the writer's own generated XSD is used.
     */
    public function schema(string $namespace, ?string $location = null, ?string $xsd = null): self
    {
        $this->schema = ['namespace' => $namespace, 'location' => $location, 'xsd' => $xsd];
        return $this;
    }

    /**
     * Adds a column per entry, each reading the row's key or property of the same name.
     *
     * @param array<string, string> $columns key => header label, in output order.
     */
    public function columns(array $columns): self
    {
        foreach ($columns as $key => $label) {
            $this->column($key, $label);
        }
        return $this;
    }

    /**
     * Adds one column after the ones already defined.
     *
     * @param string $key The row's array key or public property to read.
     * @param string $label Header text in CSV, HTML, XLSX and ODS.
     * @param (Closure(never): mixed)|null $value Computes the cell from the row, typed for the rows it gets; null reads the row's key/property.
     * @param string|null $name JSON key / XML element name; default $key.
     * @param CellType|null $type Declared type for the generated XSD; null types each value as it comes.
     */
    public function column(string $key, string $label, ?Closure $value = null, ?string $name = null, ?CellType $type = null): self
    {
        $this->columns[] = ['key' => $key, 'label' => $label, 'name' => $name ?? $key, 'type' => $type, 'value' => $value, 'meta' => []];
        return $this;
    }

    /**
     * Attaches a writer-specific attribute object (e.g. #[Fixed]) to the column added last, keyed by its class.
     *
     * @throws InvalidArgumentException when no column has been added yet
     */
    public function meta(object $meta): self
    {
        if ($this->columns === []) {
            throw new InvalidArgumentException('meta() needs a column to attach to; call column() first.');
        }
        $this->columns[count($this->columns) - 1]['meta'][get_class($meta)] = $meta;
        return $this;
    }

    /**
     * Columns from the #[Column] properties of a row class, in declaration order; every other
     * attribute on such a property (e.g. #[Fixed]) becomes column meta.
     * @param class-string $class
     */
    public function columnsOf(string $class): self
    {
        foreach ((new ReflectionClass($class))->getProperties() as $prop) {
            $column = ($prop->getAttributes(Column::class)[0] ?? null)?->newInstance();
            if ($column === null) {
                continue;
            }
            $this->column($prop->getName(), $column->label, name: $column->name, type: $column->type);
            foreach ($prop->getAttributes() as $attr) {
                if ($attr->getName() !== Column::class) {
                    $this->meta($attr->newInstance());
                }
            }
        }
        return $this;
    }

    /**
     * The rows to write, iterated when the file is written.
     *
     * @param iterable<mixed> $rows Arrays or objects; a generator is read one row at a time, never held as a whole.
     */
    public function rows(iterable $rows): self
    {
        $this->rows = $rows;
        return $this;
    }

    /** The download's file name as given to file(). */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /** The writer name: the explicit format, else the lowercased extension. */
    public function getFormat(): string
    {
        return $this->format;
    }

    /** The title given to title(), else the file name without its extension. */
    public function getTitle(): string
    {
        return $this->title ?? (string) pathinfo($this->filename, PATHINFO_FILENAME);
    }

    /**
     * What schema() set, or null.
     *
     * @return array{namespace: string, location: string|null, xsd: string|null}|null
     */
    public function getSchema(): ?array
    {
        return $this->schema;
    }

    /**
     * The columns in output order, each as `{key, label, name, type, value, meta}`.
     *
     * @return list<ColumnDef>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * The iterable given to rows(); empty by default.
     *
     * @return iterable<mixed>
     */
    public function getRows(): iterable
    {
        return $this->rows;
    }
}
