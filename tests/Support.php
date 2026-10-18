<?php

namespace Waypoint\Export\Tests;

use DateTimeImmutable;
use Waypoint\Export\{Cells, CellType, ExportOptions, Sheet};

final class Support
{
    public static function options(?callable $configure = null): ExportOptions
    {
        $options = new ExportOptions();
        if ($configure !== null) {
            $configure($options);
        }
        return $options;
    }

    public static function cells(?callable $configure = null): Cells
    {
        return new Cells(self::options($configure));
    }

    /** id, name, city, amount, active, since, role, note(null). */
    public static function sheet(string $filename = 'x.csv'): Sheet
    {
        return Sheet::file($filename)->title('Users')
            ->columns(['id' => 'ID', 'name' => 'Name', 'amount' => 'Betrag'])
            ->column('active', 'Aktiv', type: CellType::Bool)
            ->column('since', 'Seit', type: CellType::Date)
            ->column('role', 'Rolle')
            ->column('note', 'Notiz')
            ->rows([
                ['id' => 1, 'name' => 'Zoë', 'amount' => 1234.5, 'active' => true, 'since' => new DateTimeImmutable('2026-09-16 21:30:00+02:00'), 'role' => UserRole::Admin, 'note' => null],
                ['id' => 2, 'name' => 'a;b "q"', 'amount' => -3, 'active' => false, 'since' => null, 'role' => '=SUM(A1)', 'note' => 'ok'],
            ]);
    }

    public static function write(\Waypoint\Export\Writer $writer, Sheet $sheet): string
    {
        return implode('', iterator_to_array($writer->stream($sheet), false));
    }
}
