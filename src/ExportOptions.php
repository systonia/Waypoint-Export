<?php

namespace Waypoint\Export;

/** Configure with `$app->configure(function (ExportOptions $opts) { ... })`. Defaults open cleanly in a German/European Excel. */
class ExportOptions
{
    /** Date format in the text formats (CSV, HTML, fixed width); JSONL and XML always write ISO 8601. */
    public string $dateFormat = 'd.m.Y H:i';

    /** Number format code for date cells in XLSX. */
    public string $spreadsheetDateFormat = 'dd.mm.yyyy hh:mm';

    /** Decimal separator for floats in the text formats; ',' is what a German Excel reads as a number. */
    public string $decimalSeparator = ',';

    /**
     * How booleans read in the text formats: [true, false].
     * @var array{string, string}
     */
    public array $booleans = ['1', '0'];

    /** Settings of the CSV writer. */
    public CsvOptions $csv;

    /** Settings of the XML writer. */
    public XmlOptions $xml;

    public function __construct()
    {
        $this->csv = new CsvOptions();
        $this->xml = new XmlOptions();
    }
}
