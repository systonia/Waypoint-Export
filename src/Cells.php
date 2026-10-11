<?php

namespace Waypoint\Export;

use BackedEnum;
use DateTimeInterface;
use Stringable;

/**
 * The one place that reads a row and types a value. Writers get Cells and
 * choose a representation: text() for human formats, machine() for JSONL/XML.
 */
final class Cells
{
    public function __construct(private readonly ExportOptions $options)
    {
    }

    /**
     * Every cell of one row, in column order.
     * @param array<int, array{key: string, value: \Closure|null}> $columns
     * @return list<Cell>
     */
    public function row(array $columns, mixed $row): array
    {
        $cells = [];
        foreach ($columns as $column) {
            $cells[] = $this->cell($column['value'] !== null ? ($column['value'])($row) : self::read($row, $column['key']));
        }
        return $cells;
    }

    /** The row's key (array) or public property (object); null when absent. */
    public static function read(mixed $row, string $key): mixed
    {
        if (is_array($row)) {
            return $row[$key] ?? null;
        }
        if (is_object($row)) {
            return $row->{$key} ?? null;
        }
        return null;
    }

    public function cell(mixed $value): Cell
    {
        return match (true) {
            $value === null => new Cell(CellType::Empty, null),
            is_bool($value) => new Cell(CellType::Bool, $value),
            is_int($value), is_float($value) => new Cell(CellType::Number, $value),
            $value instanceof DateTimeInterface => new Cell(CellType::Date, $value),
            $value instanceof BackedEnum => $this->cell($value->value),
            is_string($value), $value instanceof Stringable => new Cell(CellType::Text, (string) $value),
            default => new Cell(CellType::Text, json_encode($value, JSON_UNESCAPED_UNICODE) ?: ''),
        };
    }

    /** Human representation: options' decimal separator, date format and boolean words. */
    public function text(Cell $cell): string
    {
        return match ($cell->type) {
            CellType::Empty => '',
            CellType::Bool => $cell->value === true ? $this->options->booleans[0] : $this->options->booleans[1],
            CellType::Number => str_replace('.', $this->options->decimalSeparator, self::number($cell->value)),
            CellType::Date => $cell->value instanceof DateTimeInterface ? $cell->value->format($this->options->dateFormat) : '',
            CellType::Text => is_string($cell->value) ? $cell->value : '',
        };
    }

    /** Machine representation: '.' decimals, ISO 8601 dates, true/false. */
    public static function machine(Cell $cell): string
    {
        return match ($cell->type) {
            CellType::Empty => '',
            CellType::Bool => $cell->value === true ? 'true' : 'false',
            CellType::Number => self::number($cell->value),
            CellType::Date => $cell->value instanceof DateTimeInterface ? $cell->value->format(DATE_ATOM) : '',
            CellType::Text => is_string($cell->value) ? $cell->value : '',
        };
    }

    /** Ints as-is, floats without trailing zeros (up to six decimals), never in exponent notation. */
    public static function number(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        $text = rtrim(rtrim(number_format(is_float($value) ? $value : 0.0, 6, '.', ''), '0'), '.');
        return $text === '-0' ? '0' : $text;
    }

    /** A leading quote for cells a spreadsheet would run as a formula; numbers are left alone. */
    public static function guardFormula(string $text): string
    {
        return $text !== '' && strpbrk($text[0], '=+-@') !== false && !is_numeric($text) ? "'" . $text : $text;
    }

    /** Excel/ODS serial day number (days since 1899-12-30, local time) for a date. */
    public static function serial(DateTimeInterface $date): float
    {
        return ($date->getTimestamp() + $date->getOffset()) / 86400 + 25569;
    }
}
