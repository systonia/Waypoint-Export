<?php

namespace Waypoint\Export;

use Attribute;

/**
 * Marks a public property of a row class as an export column; `Sheet::columnsOf(RowClass::class)`
 * collects them in declaration order. $name is the JSON key / XML element (default: the property),
 * $type feeds the XSD and spreadsheet formats (default: derived from each value).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column
{
    /**
     * @param string $label Header text in CSV, HTML, XLSX and ODS.
     * @param string|null $name JSON key / XML element name; default: the property name.
     * @param CellType|null $type Declared type for the generated XSD; default: typed per value.
     */
    public function __construct(public string $label, public ?string $name = null, public ?CellType $type = null)
    {
    }
}
