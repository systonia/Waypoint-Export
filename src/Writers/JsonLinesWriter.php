<?php

namespace Waypoint\Export\Writers;

use DateTimeInterface;
use Waypoint\Export\{Cell, Cells, CellType, Sheet, Writer};

/** One JSON object per line, keyed by column name, with native types (numbers, booleans, null, ISO 8601 dates). */
final class JsonLinesWriter implements Writer
{
    public function __construct(private readonly Cells $cells)
    {
    }

    public function extension(): string
    {
        return 'jsonl';
    }

    public function contentType(): string
    {
        return 'application/x-ndjson; charset=utf-8';
    }

    public function stream(Sheet $sheet): iterable
    {
        $columns = $sheet->getColumns();
        foreach ($sheet->getRows() as $row) {
            $object = [];
            foreach ($this->cells->row($columns, $row) as $i => $cell) {
                $object[$columns[$i]['name']] = self::json($cell);
            }
            yield (json_encode($object, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') . "\n";
        }
    }

    private static function json(Cell $cell): string|int|float|bool|null
    {
        return match ($cell->type) {
            CellType::Empty => null,
            CellType::Date => $cell->value instanceof DateTimeInterface ? $cell->value->format(DATE_ATOM) : null,
            CellType::Text, CellType::Number, CellType::Bool => $cell->value instanceof DateTimeInterface ? null : $cell->value,
        };
    }
}
