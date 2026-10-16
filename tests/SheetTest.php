<?php

namespace Waypoint\Export\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Waypoint\Export\{CellType, Column, Sheet};

final class SheetTest extends TestCase
{
    public function testFormatComesFromTheExtensionUnlessGiven(): void
    {
        $this->assertSame('xlsx', Sheet::file('Kunden 2026.XLSX')->getFormat());
        $this->assertSame('fixed', Sheet::file('lieferung.txt', 'fixed')->getFormat());
        $this->assertSame('csv', Sheet::csv('a.csv')->getFormat());
        $this->assertSame('Kunden 2026', Sheet::file('Kunden 2026.XLSX')->getTitle());
        $this->assertSame('Users', Sheet::file('a.csv')->title('Users')->getTitle());
    }

    public function testFilenameMustBePlainAndCarryAnExtension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Sheet::file('../etc/passwd.csv');
    }

    public function testAFilenameWithoutExtensionNeedsAnExplicitFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Sheet::file('README');
    }

    public function testColumnsCarryNameTypeClosureAndMeta(): void
    {
        $meta = new Column('unused');
        $sheet = Sheet::file('a.csv')->columns(['a' => 'A'])->column('b', 'B', fn(Row $r): int => 1, name: 'bee', type: CellType::Number)->meta($meta)->rows([['a' => 1]]);

        $columns = $sheet->getColumns();
        $this->assertSame(['a', 'A', 'a', null, null, []], [$columns[0]['key'], $columns[0]['label'], $columns[0]['name'], $columns[0]['type'], $columns[0]['value'], $columns[0]['meta']]);
        $this->assertSame(['bee', CellType::Number], [$columns[1]['name'], $columns[1]['type']]);
        $this->assertSame([Column::class => $meta], $columns[1]['meta']);
        $this->assertSame([['a' => 1]], $sheet->getRows());
        $this->assertNull($sheet->getSchema());
        $this->assertSame(['namespace' => 'urn:x', 'location' => 'x.xsd', 'xsd' => null], $sheet->schema('urn:x', 'x.xsd')->getSchema());
    }

    public function testMetaNeedsAColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Sheet::file('a.csv')->meta(new Column('x'));
    }

    public function testColumnsOfCollectsColumnAttributesAndTheirCompanions(): void
    {
        $columns = Sheet::file('a.csv')->columnsOf(Row::class)->getColumns();

        $this->assertSame(['id', 'name'], array_column($columns, 'key'));
        $this->assertSame(['ID', 'Name'], array_column($columns, 'label'));
        $this->assertSame(['id', 'fullName'], array_column($columns, 'name'));
        $this->assertSame([CellType::Number, null], array_column($columns, 'type'));
    }
}
