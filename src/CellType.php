<?php

namespace Waypoint\Export;

/** What a cell holds; each Writer decides how to represent it (a real number in XLSX, `1234,5` in CSV, `1234.5` in JSONL). */
enum CellType: string
{
    case Empty = 'empty';
    case Text = 'text';
    case Number = 'number';
    case Bool = 'bool';
    case Date = 'date';
}
