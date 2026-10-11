<?php

namespace Waypoint\Export\Tests;

use PHPUnit\Framework\TestCase;
use Waypoint\Export\Sheet;
use Waypoint\Export\Writers\{OdsWriter, XlsxWriter};
use ZipArchive;

final class SpreadsheetWritersTest extends TestCase
{
    /** @return array<string, string> entry name => content, in archive order */
    private function unzip(string $bytes): array
    {
        $path = tempnam(sys_get_temp_dir(), 'ztest');
        file_put_contents($path, $bytes);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $entries[$name] = (string) $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($path);
        return $entries;
    }

    public function testXlsxIsAValidWorkbookWithTypedCells(): void
    {
        $writer = new XlsxWriter(Support::cells(), Support::options());
        $bytes = Support::write($writer, Support::sheet('x.xlsx')->title('Kunden/2026 mit einem sehr langen Blattnamen'));

        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $writer->contentType());
        $this->assertStringStartsWith('PK', $bytes);
        $files = $this->unzip($bytes);
        $this->assertSame(['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'], array_keys($files));
        $this->assertStringContainsString('<sheet name="Kunden_2026 mit einem sehr lang" sheetId="1"', $files['xl/workbook.xml'], 'sheet names are sanitized and cut to 31 chars');
        $this->assertStringContainsString('formatCode="dd.mm.yyyy hh:mm"', $files['xl/styles.xml']);

        $sheet = $files['xl/worksheets/sheet1.xml'];
        $this->assertStringContainsString('<c r="A1" t="inlineStr" s="2"><is><t xml:space="preserve">ID</t></is></c>', $sheet, 'bold header');
        $this->assertStringContainsString('<c r="A2"><v>1</v></c><c r="B2" t="inlineStr"><is><t xml:space="preserve">Zoë</t></is></c><c r="C2"><v>1234.5</v></c><c r="D2" t="b"><v>1</v></c><c r="E2" s="1"><v>46281.895833333</v></c>', $sheet);
        $this->assertStringContainsString('<c r="G3" t="inlineStr"><is><t xml:space="preserve">ok</t></is></c></row>', $sheet);
        $this->assertStringNotContainsString('r="G2"', $sheet, 'empty cells are omitted');
        $this->assertStringContainsString('<c r="F3" t="inlineStr"><is><t xml:space="preserve">=SUM(A1)</t></is></c>', $sheet, 'inline strings are never formulas, so no guard');
        $this->assertStringContainsString('<col min="3" max="3" width="10" customWidth="1"/>', $sheet);
    }

    public function testCellReferences(): void
    {
        $this->assertSame('A1', XlsxWriter::ref(0, 1));
        $this->assertSame('Z9', XlsxWriter::ref(25, 9));
        $this->assertSame('AA10', XlsxWriter::ref(26, 10));
        $this->assertSame('AZ1', XlsxWriter::ref(51, 1));
    }

    public function testOdsHasAStoredMimetypeFirstAndTypedCells(): void
    {
        $writer = new OdsWriter(Support::cells());
        $bytes = Support::write($writer, Support::sheet('x.ods'));

        $this->assertSame(OdsWriter::MIME, $writer->contentType());
        $this->assertSame('mimetype', substr($bytes, 30, 8), 'mimetype is the first entry');
        $this->assertSame(OdsWriter::MIME, substr($bytes, 38, strlen(OdsWriter::MIME)), 'and stored uncompressed');
        $files = $this->unzip($bytes);
        $this->assertSame(['mimetype', 'META-INF/manifest.xml', 'content.xml'], array_keys($files));
        $content = $files['content.xml'];
        $this->assertStringContainsString('<table:table table:name="Users">', $content);
        $this->assertStringContainsString('<table:table-cell office:value-type="float" office:value="1234.5"><text:p>1234,5</text:p></table:table-cell>', $content);
        $this->assertStringContainsString('<table:table-cell office:value-type="boolean" office:boolean-value="true"><text:p>1</text:p></table:table-cell>', $content);
        $this->assertStringContainsString('<table:table-cell office:value-type="date" office:date-value="2026-09-16T21:30:00"><text:p>16.09.2026 21:30</text:p></table:table-cell>', $content);
        $this->assertStringContainsString('<table:table-cell office:value-type="string"><text:p>admin</text:p></table:table-cell><table:table-cell/></table:table-row>', $content);
    }

    public function testAnEmptySheetStillProducesAFile(): void
    {
        $files = $this->unzip(Support::write(new OdsWriter(Support::cells()), Sheet::file('e.ods')));
        $this->assertStringContainsString('table:number-columns-repeated="1"', $files['content.xml']);
    }
}
