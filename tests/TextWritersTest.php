<?php

namespace Waypoint\Export\Tests;

use PHPUnit\Framework\TestCase;
use Waypoint\Export\{CsvOptions, ExportOptions, Sheet};
use Waypoint\Export\Writers\{CsvWriter, HtmlWriter, JsonLinesWriter};

final class TextWritersTest extends TestCase
{
    public function testCsvDefaultsAreExcelFriendlyWithFormulaGuardAndQuoting(): void
    {
        $writer = new CsvWriter(Support::cells(), Support::options());
        $csv = Support::write($writer, Support::sheet());

        $this->assertSame('text/csv; charset=windows-1252', $writer->contentType());
        $this->assertSame(
            "ID;Name;Betrag;Aktiv;Seit;Rolle;Notiz\r\n1;Zo\xEB;1234,5;1;16.09.2026 21:30;admin;\r\n2;\"a;b \"\"q\"\"\";-3;0;;'=SUM(A1);ok\r\n",
            $csv
        );
    }

    public function testCsvUtf8VariantWithOtherDelimiterAndNoGuard(): void
    {
        $options = Support::options(function (ExportOptions $o) {
            $o->csv->encoding = 'UTF-8';
            $o->csv->delimiter = ',';
            $o->csv->lineEnding = "\n";
            $o->csv->guardFormulas = false;
            $o->decimalSeparator = '.';
        });
        $writer = new CsvWriter(new \Waypoint\Export\Cells($options), $options);

        $csv = Support::write($writer, Sheet::file('x.csv')->columns(['n' => 'N', 'f' => 'F'])->rows([['n' => 'Zoë', 'f' => '=1']]));

        $this->assertSame("\xEF\xBB\xBFN,F\nZoë,=1\n", $csv);
        $this->assertSame('text/csv; charset=utf-8', $writer->contentType());
    }

    public function testJsonLinesWritesNativeTypesKeyedByColumnName(): void
    {
        $writer = new JsonLinesWriter(Support::cells());
        $out = Support::write($writer, Support::sheet('x.jsonl'));

        $this->assertSame('application/x-ndjson; charset=utf-8', $writer->contentType());
        $this->assertSame(
            '{"id":1,"name":"Zoë","amount":1234.5,"active":true,"since":"2026-09-16T21:30:00+02:00","role":"admin","note":null}' . "\n"
            . '{"id":2,"name":"a;b \"q\"","amount":-3,"active":false,"since":null,"role":"=SUM(A1)","note":"ok"}' . "\n",
            $out
        );
        $this->assertSame('{"fullName":"Ada"}' . "\n", Support::write($writer, Sheet::file('r.jsonl')->column('name', 'Name', name: 'fullName')->rows([new Row()])));
    }

    public function testHtmlIsAStandalonePageAndFragmentIsJustTheTable(): void
    {
        $writer = new HtmlWriter(Support::cells());
        $page = Support::write($writer, Support::sheet('x.html'));

        $this->assertStringStartsWith('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Users</title>', $page);
        $this->assertStringContainsString('<table class="wp-sheet"><caption>Users</caption><thead><tr><th scope="col">ID</th>', $page);
        $this->assertStringContainsString('<td class="wp-sheet-num">1234,5</td><td>1</td><td>16.09.2026 21:30</td><td>admin</td><td></td></tr>', $page);
        $this->assertStringContainsString('<td>a;b &quot;q&quot;</td>', $page);
        $this->assertStringEndsWith("</tbody></table>\n</body></html>\n", $page);

        $fragment = implode('', iterator_to_array($writer->fragment(Support::sheet('x.html')), false));
        $this->assertStringStartsWith('<table class="wp-sheet">', $fragment);
        $this->assertStringNotContainsString('<html', $fragment);
    }
}
