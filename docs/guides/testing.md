---
title: Testing exports
description: Check downloads through the app with Waypoint's TestCase, and writers on their own.
order: 80
---

Exports are ordinary routes, so Waypoint's testing kit (`Waypoint\Testing\TestCase`, with `phpunit/phpunit` as a dev dependency) drives them through the whole app: guards, the controller, the plugin and the writer.

```php
<?php

use Waypoint\Export\{ExportOptions, ExportPlugin};
use Waypoint\Testing\TestCase;

final class ExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = $this->app();
        $app->plugin(new ExportPlugin());
        $app->configure(function (ExportOptions $o) {
            $o->csv->encoding = 'UTF-8';
        });
        $app->attach([ReportsController::class]);
    }

    public function testRevenueAsCsv(): void
    {
        $this->get('/reports/revenue.csv')
            ->assertOk()
            ->assertContentType('text/csv')
            ->assertHeader('Content-Disposition', 'attachment; filename="revenue.csv"; filename*=UTF-8\'\'revenue.csv')
            ->assertBody("\xEF\xBB\xBFMonth;Net;Paid\r\n2026-09;18250,75;1\r\n2026-10;9100;0\r\n");
    }
}
```

with this controller:

```php
#[Controller('/reports')]
final class ReportsController
{
    #[Get('/revenue.{format}')]
    public function revenue(string $format): Sheet
    {
        return Sheet::file("revenue.$format")
            ->title('Revenue')
            ->columns(['month' => 'Month', 'net' => 'Net', 'paid' => 'Paid'])
            ->rows([
                ['month' => '2026-09', 'net' => 18250.75, 'paid' => true],
                ['month' => '2026-10', 'net' => 9100, 'paid' => false],
            ]);
    }
}
```

UTF-8 in the test keeps the expected body readable; the byte order mark `\xEF\xBB\xBF` comes first. With the default Windows-1252, compare against `mb_convert_encoding($expected, 'Windows-1252', 'UTF-8')`.

## Structured formats

JSON Lines is easiest to check per value:

```php
public function testRevenueAsJsonLines(): void
{
    $lines = array_map(json_decode(...), explode("\n", trim($this->get('/reports/revenue.jsonl')->assertOk()->body())));
    $this->assertSame(18250.75, $lines[0]->net);
    $this->assertTrue($lines[0]->paid);
}
```

XLSX and ODS are zip files; open the body with `ZipArchive` and look at the sheet:

```php
public function testRevenueAsXlsx(): void
{
    $file = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($file, $this->get('/reports/revenue.xlsx')->assertOk()->body());
    $zip = new ZipArchive();
    $this->assertTrue($zip->open($file));
    $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    unlink($file);

    $this->assertStringContainsString('<c r="B2"><v>18250.75</v></c>', $sheet);   // a real number
}
```

The ODS table is in `content.xml`. For XML exports, turning on `xml->validate` in the test's `configure()` makes every request check the document against its schema; a mismatch answers 500.

## Errors

An unknown format is a server error; the testing kit sees the 500 and the log line names the installed formats:

```php
public function testAnUnknownFormatIsAServerError(): void
{
    $this->get('/reports/revenue.pdf')->assertStatus(500);
}
```

A route that takes the format from the path can answer 404 instead by asking the registry first, see [Errors and limits](edge-cases.md#unknown-formats).

## Writers without the app

A writer is a plain object; build it with `ExportOptions` and `Cells` and join its chunks:

```php
use Waypoint\Export\{Cells, ExportOptions, Sheet};
use Waypoint\Export\Writers\CsvWriter;

$options = new ExportOptions();
$options->csv->encoding = 'UTF-8';
$writer = new CsvWriter(new Cells($options), $options);

$csv = implode('', iterator_to_array($writer->stream(
    Sheet::file('x.csv')->columns(['n' => 'N'])->rows([['n' => '=1+1']])
), false));

$this->assertSame("\xEF\xBB\xBFN\r\n'=1+1\r\n", $csv);
```

The same works for a writer of your own, such as the [Markdown writer](custom-formats.md#a-markdown-table-writer). `iterator_to_array(..., false)` matters: a generator's keys repeat.

See also: [Waypoint's testing kit](https://github.com/systonia/Waypoint/blob/main/README.md#testing).
