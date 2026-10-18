<?php

namespace Waypoint\Export\Writers;

use Waypoint\Export\{Cells, CellType, Sheet, Writer};

/** A standalone HTML page with one table; fragment() gives the bare `<table>` for `$this->sheet()` in views. */
final class HtmlWriter implements Writer
{
    public function __construct(private readonly Cells $cells)
    {
    }

    public function extension(): string
    {
        return 'html';
    }

    public function contentType(): string
    {
        return 'text/html; charset=utf-8';
    }

    public function stream(Sheet $sheet): iterable
    {
        yield '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . self::e($sheet->getTitle()) . '</title>'
            . '<style>body{font:14px system-ui,sans-serif;margin:24px}table{border-collapse:collapse}caption{text-align:left;font-weight:600;margin-bottom:8px}'
            . 'th,td{border:1px solid #ccc;padding:4px 8px;text-align:left}th{background:#f3f3f3}td.wp-sheet-num{text-align:right;font-variant-numeric:tabular-nums}</style>'
            . "</head><body>\n";
        yield from $this->fragment($sheet);
        yield "</body></html>\n";
    }

    /** @return iterable<string> */
    public function fragment(Sheet $sheet): iterable
    {
        $columns = $sheet->getColumns();
        yield '<table class="wp-sheet"><caption>' . self::e($sheet->getTitle()) . '</caption><thead><tr>'
            . implode('', array_map(fn (array $c): string => '<th scope="col">' . self::e($c['label']) . '</th>', $columns))
            . "</tr></thead><tbody>\n";
        foreach ($sheet->getRows() as $row) {
            $tds = '';
            foreach ($this->cells->row($columns, $row) as $cell) {
                $tds .= $cell->type === CellType::Number ? '<td class="wp-sheet-num">' : '<td>';
                $tds .= self::e($this->cells->text($cell)) . '</td>';
            }
            yield "<tr>$tds</tr>\n";
        }
        yield "</tbody></table>\n";
    }

    private static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
