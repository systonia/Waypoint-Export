<?php

namespace Waypoint\Export;

use DateTimeInterface;

/** A typed cell: Text carries a string, Number an int|float, Bool a bool, Date a DateTimeInterface, Empty null. */
final class Cell
{
    public function __construct(public readonly CellType $type, public readonly string|int|float|bool|DateTimeInterface|null $value)
    {
    }
}
