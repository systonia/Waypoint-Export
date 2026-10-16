<?php

namespace Waypoint\Export;

/** What a cell holds; each Writer decides how to represent it (a real number in XLSX, `1234,5` in CSV, `1234.5` in JSONL). */
enum CellType: string
{
    /** null: an empty cell. */
    case Empty = 'empty';
    /** A string, a Stringable, a backed enum's value, or the JSON of an array or object. */
    case Text = 'text';
    /** An int or a float. */
    case Number = 'number';
    /** A bool. */
    case Bool = 'bool';
    /** A DateTimeInterface. */
    case Date = 'date';
}
