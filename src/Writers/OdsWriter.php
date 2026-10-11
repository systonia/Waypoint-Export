<?php

namespace Waypoint\Export\Writers;

use DateTimeInterface;
use Waypoint\Export\{Cell, Cells, CellType, Sheet, Writer};

/** A minimal OpenDocument spreadsheet: stored mimetype first, manifest, one table in content.xml with typed cells. */
final class OdsWriter implements Writer
{
    public const MIME = 'application/vnd.oasis.opendocument.spreadsheet';

    public function __construct(private readonly Cells $cells)
    {
    }

    public function extension(): string
    {
        return 'ods';
    }

    public function contentType(): string
    {
        return self::MIME;
    }

    public function stream(Sheet $sheet): iterable
    {
        $zip = new ZipPackage();
        $zip->addString('mimetype', self::MIME, store: true);
        $zip->addString('META-INF/manifest.xml', '<?xml version="1.0" encoding="UTF-8"?><manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2">'
            . '<manifest:file-entry manifest:full-path="/" manifest:media-type="' . self::MIME . '"/>'
            . '<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/></manifest:manifest>');

        $columns = $sheet->getColumns();
        $content = $zip->tempFile();
        $out = fopen($content, 'wb');
        if ($out === false) {
            return; // @codeCoverageIgnore
        }
        fwrite($out, '<?xml version="1.0" encoding="UTF-8"?><office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" office:version="1.2">'
            . '<office:body><office:spreadsheet><table:table table:name="' . self::e($sheet->getTitle()) . '">'
            . '<table:table-column table:number-columns-repeated="' . max(1, count($columns)) . '"/>');
        fwrite($out, '<table:table-row>' . implode('', array_map(fn(array $c): string => self::string($c['label']), $columns)) . '</table:table-row>');
        foreach ($sheet->getRows() as $row) {
            fwrite($out, '<table:table-row>' . implode('', array_map($this->xml(...), $this->cells->row($columns, $row))) . '</table:table-row>');
        }
        fwrite($out, '</table:table></office:spreadsheet></office:body></office:document-content>');
        fclose($out);
        $zip->addFile('content.xml', $content);
        yield from $zip->stream();
    }

    private function xml(Cell $cell): string
    {
        return match ($cell->type) {
            CellType::Empty => '<table:table-cell/>',
            CellType::Number => '<table:table-cell office:value-type="float" office:value="' . Cells::number($cell->value) . '"><text:p>' . self::e($this->cells->text($cell)) . '</text:p></table:table-cell>',
            CellType::Bool => '<table:table-cell office:value-type="boolean" office:boolean-value="' . ($cell->value === true ? 'true' : 'false') . '"><text:p>' . self::e($this->cells->text($cell)) . '</text:p></table:table-cell>',
            CellType::Date => $cell->value instanceof DateTimeInterface
                ? '<table:table-cell office:value-type="date" office:date-value="' . $cell->value->format('Y-m-d\TH:i:s') . '"><text:p>' . self::e($this->cells->text($cell)) . '</text:p></table:table-cell>'
                : '<table:table-cell/>',
            CellType::Text => self::string($this->cells->text($cell)),
        };
    }

    private static function string(string $text): string
    {
        return '<table:table-cell office:value-type="string"><text:p>' . self::e($text) . '</text:p></table:table-cell>';
    }

    private static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
