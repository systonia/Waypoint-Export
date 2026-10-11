<?php

namespace Waypoint\Export;

use Closure;
use InvalidArgumentException;
use ReflectionClass;

/**
 * A downloadable table. Build it with the fluent API and return it from a
 * route; ExportPlugin picks the Writer by the file's extension (or $format).
 *
 *   return Sheet::file('users.xlsx')
 *       ->columns(['id' => 'ID', 'email' => 'Email'])
 *       ->column('name', 'Name', fn(User $u): string => $u->displayName())
 *       ->rows($this->users->all());
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

    /** @param string|null $format Writer name; default: the file's extension (`users.xlsx` -> xlsx). */
    public static function file(string $filename, ?string $format = null): self
    {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        return new self($filename, $format ?? $extension);
    }

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

    /** @param array<string, string> $columns key => header label, in output order. */
    public function columns(array $columns): self
    {
        foreach ($columns as $key => $label) {
            $this->column($key, $label);
        }
        return $this;
    }

    /**
     * @param (Closure(mixed): mixed)|null $value Computes the cell from the row; null reads the row's key/property.
     * @param string|null $name JSON key / XML element name; default $key.
     */
    public function column(string $key, string $label, ?Closure $value = null, ?string $name = null, ?CellType $type = null): self
    {
        $this->columns[] = ['key' => $key, 'label' => $label, 'name' => $name ?? $key, 'type' => $type, 'value' => $value, 'meta' => []];
        return $this;
    }

    /** Attaches a writer-specific attribute object (e.g. #[Fixed]) to the column added last. */
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

    /** @param iterable<mixed> $rows Arrays or objects; a generator streams without holding every row. */
    public function rows(iterable $rows): self
    {
        $this->rows = $rows;
        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getTitle(): string
    {
        return $this->title ?? (string) pathinfo($this->filename, PATHINFO_FILENAME);
    }

    /** @return array{namespace: string, location: string|null, xsd: string|null}|null */
    public function getSchema(): ?array
    {
        return $this->schema;
    }

    /** @return list<ColumnDef> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @return iterable<mixed> */
    public function getRows(): iterable
    {
        return $this->rows;
    }
}
