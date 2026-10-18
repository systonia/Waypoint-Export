<?php

namespace Waypoint\Export\Writers;

use DateTimeInterface;
use Waypoint\Export\{Cell, Cells, CellType, ExportOptions, Sheet, Writer};

/** A minimal, valid OOXML workbook: one sheet, inline strings, real numbers and booleans, dates as serials with a number format, bold header. */
final class XlsxWriter implements Writer
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    public function __construct(private readonly Cells $cells, private readonly ExportOptions $options)
    {
    }

    public function extension(): string
    {
        return 'xlsx';
    }

    public function contentType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function stream(Sheet $sheet): iterable
    {
        $zip = new ZipPackage();
        $columns = $sheet->getColumns();
        $sheetFile = $zip->tempFile();
        $out = fopen($sheetFile, 'wb');
        if ($out === false) {
            return; // @codeCoverageIgnore
        }
        $cols = '';
        foreach ($columns as $i => $column) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . max(10, mb_strlen($column['label']) + 2) . '" customWidth="1"/>';
        }
        fwrite($out, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="' . self::NS . "\"><cols>$cols</cols><sheetData>");
        fwrite($out, '<row r="1">' . implode('', array_map(fn (int $i, array $c): string => self::inline(self::ref($i, 1), $c['label'], 2), array_keys($columns), $columns)) . '</row>');
        $r = 1;
        foreach ($sheet->getRows() as $row) {
            $r++;
            $line = "<row r=\"$r\">";
            foreach ($this->cells->row($columns, $row) as $i => $cell) {
                $line .= $this->xml(self::ref($i, $r), $cell);
            }
            fwrite($out, "$line</row>");
        }
        fwrite($out, '</sheetData></worksheet>');
        fclose($out);

        $title = htmlspecialchars(mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '_', $sheet->getTitle()), 0, 31), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $zip->addString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="' . self::NS . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . "<sheets><sheet name=\"$title\" sheetId=\"1\" r:id=\"rId1\"/></sheets></workbook>");
        $zip->addString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="' . self::NS . '">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="' . htmlspecialchars($this->options->spreadsheetDateFormat, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs></styleSheet>');
        $zip->addFile('xl/worksheets/sheet1.xml', $sheetFile);
        yield from $zip->stream();
    }

    private function xml(string $ref, Cell $cell): string
    {
        return match ($cell->type) {
            CellType::Empty => '',
            CellType::Number => "<c r=\"$ref\"><v>" . Cells::number($cell->value) . '</v></c>',
            CellType::Bool => "<c r=\"$ref\" t=\"b\"><v>" . ($cell->value === true ? '1' : '0') . '</v></c>',
            CellType::Date => $cell->value instanceof DateTimeInterface ? "<c r=\"$ref\" s=\"1\"><v>" . Cells::serial($cell->value) . '</v></c>' : '',
            CellType::Text => self::inline($ref, $this->cells->text($cell), 0),
        };
    }

    private static function inline(string $ref, string $text, int $style): string
    {
        $s = $style > 0 ? " s=\"$style\"" : '';
        return "<c r=\"$ref\" t=\"inlineStr\"$s><is><t xml:space=\"preserve\">" . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
    }

    /** A1-style reference for a zero-based column and one-based row. */
    public static function ref(int $column, int $row): string
    {
        $letters = '';
        for ($n = $column + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letters = chr(65 + ($n - 1) % 26) . $letters;
        }
        return $letters . $row;
    }
}
