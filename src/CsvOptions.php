<?php

namespace Waypoint\Export;

/** `ExportOptions::$csv`. */
class CsvOptions
{
    /** Field separator; Excel expects the list separator of its locale (';' in most of Europe, ',' in the US). */
    public string $delimiter = ';';

    /** 'Windows-1252' (Excel opens it directly) or 'UTF-8' (written with a BOM so Excel detects it). */
    public string $encoding = 'Windows-1252';

    /** "\r\n" is what Excel writes itself. */
    public string $lineEnding = "\r\n";

    /** Prefix cells starting with =, +, -, @ with a quote so a spreadsheet never runs them as formulas (CSV injection). */
    public bool $guardFormulas = true;
}
