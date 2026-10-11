<?php

namespace Waypoint\Export\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Waypoint\Export\{Cell, Cells, CellType, ExportOptions};

final class CellsTest extends TestCase
{
    public function testValuesAreTyped(): void
    {
        $cells = Support::cells();

        $this->assertSame(CellType::Empty, $cells->cell(null)->type);
        $this->assertSame(CellType::Bool, $cells->cell(false)->type);
        $this->assertSame(CellType::Number, $cells->cell(42)->type);
        $this->assertSame(CellType::Number, $cells->cell(1.5)->type);
        $this->assertSame(CellType::Date, $cells->cell(new DateTimeImmutable())->type);
        $this->assertSame([CellType::Text, 'admin'], [$cells->cell(Role::Admin)->type, $cells->cell(Role::Admin)->value]);
        $this->assertSame([CellType::Text, '007'], [$cells->cell('007')->type, $cells->cell('007')->value], 'numeric strings stay text');
        $this->assertSame('["a",1]', $cells->cell(['a', 1])->value);
    }

    public function testTextFollowsTheOptions(): void
    {
        $cells = Support::cells();
        $this->assertSame('1234,5', $cells->text($cells->cell(1234.5)));
        $this->assertSame('3', $cells->text($cells->cell(3.0)));
        $this->assertSame('0,000001', $cells->text($cells->cell(0.000001)));
        $this->assertSame('16.09.2026 21:30', $cells->text($cells->cell(new DateTimeImmutable('2026-09-16 21:30:00'))));
        $this->assertSame(['1', '0', ''], [$cells->text($cells->cell(true)), $cells->text($cells->cell(false)), $cells->text($cells->cell(null))]);

        $german = Support::cells(function (ExportOptions $o) {
            $o->booleans = ['Ja', 'Nein'];
            $o->decimalSeparator = '.';
            $o->dateFormat = 'Y-m-d';
        });
        $this->assertSame(['Ja', '1.25', '2026-01-02'], [$german->text($german->cell(true)), $german->text($german->cell(1.25)), $german->text($german->cell(new DateTimeImmutable('2026-01-02')))]);
    }

    public function testMachineIsLocaleFree(): void
    {
        $cells = Support::cells();
        $this->assertSame('1234.5', Cells::machine($cells->cell(1234.5)));
        $this->assertSame('true', Cells::machine($cells->cell(true)));
        $this->assertSame('2026-09-16T21:30:00+02:00', Cells::machine($cells->cell(new DateTimeImmutable('2026-09-16 21:30:00+02:00'))));
        $this->assertSame('', Cells::machine(new Cell(CellType::Date, 'not a date')));
        $this->assertSame('', $cells->text(new Cell(CellType::Date, 'not a date')));
        $this->assertSame('0', Cells::number(-0.0));
        $this->assertSame('0', Cells::number('x'));
    }

    public function testRowReadsArraysObjectsClosuresAndMissingKeys(): void
    {
        $cells = Support::cells();
        $columns = [
            ['key' => 'id', 'value' => null],
            ['key' => 'upper', 'value' => fn(mixed $row): string => 'U'],
            ['key' => 'missing', 'value' => null],
        ];

        $this->assertSame([1, 'U', null], array_map(fn(Cell $c) => $c->value, $cells->row($columns, ['id' => 1])));
        $this->assertSame([7, 'U', null], array_map(fn(Cell $c) => $c->value, $cells->row($columns, new Row())));
        $this->assertSame([null, 'U', null], array_map(fn(Cell $c) => $c->value, $cells->row($columns, 'not a row')));
    }

    public function testFormulaGuardAndSerialDates(): void
    {
        $this->assertSame("'=SUM(A1)", Cells::guardFormula('=SUM(A1)'));
        $this->assertSame("'@cmd", Cells::guardFormula('@cmd'));
        $this->assertSame('-12.5', Cells::guardFormula('-12.5'));
        $this->assertSame('+1234', Cells::guardFormula('+1234'));
        $this->assertSame("'-x", Cells::guardFormula('-x'));
        $this->assertSame('', Cells::guardFormula(''));
        $this->assertSame(46011.0, Cells::serial(new DateTimeImmutable('2025-12-20 00:00:00 UTC')));
        $this->assertSame(46011.5, Cells::serial(new DateTimeImmutable('2025-12-20 12:00:00+02:00')), 'local wall time, not UTC');
    }
}
