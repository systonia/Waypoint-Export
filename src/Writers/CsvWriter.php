<?php

namespace Waypoint\Export\Writers;

use Waypoint\Export\{Cells, CsvOptions, ExportOptions, Sheet, Writer};

/** CSV in the configured delimiter, line ending and encoding; a BOM when UTF-8. */
final class CsvWriter implements Writer
{
    private readonly CsvOptions $csv;

    public function __construct(private readonly Cells $cells, ExportOptions $options)
    {
        $this->csv = $options->csv;
    }

    public function extension(): string
    {
        return 'csv';
    }

    public function contentType(): string
    {
        return 'text/csv; charset=' . strtolower($this->csv->encoding);
    }

    public function stream(Sheet $sheet): iterable
    {
        $columns = $sheet->getColumns();
        yield ($this->utf8() ? "\xEF\xBB\xBF" : '') . $this->line(array_map(fn(array $c): string => $c['label'], $columns));
        foreach ($sheet->getRows() as $row) {
            yield $this->line(array_map($this->cells->text(...), $this->cells->row($columns, $row)));
        }
    }

    private function utf8(): bool
    {
        return strcasecmp($this->csv->encoding, 'UTF-8') === 0;
    }

    /** @param list<string> $cells */
    private function line(array $cells): string
    {
        $line = implode($this->csv->delimiter, array_map($this->quote(...), $cells)) . $this->csv->lineEnding;
        return $this->utf8() ? $line : mb_convert_encoding($line, $this->csv->encoding, 'UTF-8');
    }

    private function quote(string $cell): string
    {
        if ($this->csv->guardFormulas) {
            $cell = Cells::guardFormula($cell);
        }
        if ($cell === '' || strpbrk($cell, $this->csv->delimiter . "\"\r\n") === false) {
            return $cell;
        }
        return '"' . str_replace('"', '""', $cell) . '"';
    }
}
