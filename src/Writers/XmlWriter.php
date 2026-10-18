<?php

namespace Waypoint\Export\Writers;

use DOMDocument;
use RuntimeException;
use Waypoint\Export\{Cells, CellType, ExportOptions, Sheet, Writer, XmlOptions};

/**
 * `<title xmlns="..."><row><col>...</col></row>...</title>`; empty cells are `xsi:nil`.
 * xsd() derives a schema from the columns (declared types, else xs:string), and with
 * XmlOptions::$validate every document is checked against it before it leaves.
 */
final class XmlWriter implements Writer
{
    private const XSI = 'http://www.w3.org/2001/XMLSchema-instance';
    private readonly XmlOptions $xml;

    public function __construct(private readonly Cells $cells, ExportOptions $options)
    {
        $this->xml = $options->xml;
    }

    public function extension(): string
    {
        return 'xml';
    }

    public function contentType(): string
    {
        return 'application/xml; charset=utf-8';
    }

    public function stream(Sheet $sheet): iterable
    {
        if (!$this->xml->validate) {
            yield from $this->chunks($sheet);
            return;
        }
        $document = implode('', iterator_to_array($this->chunks($sheet), false));
        $this->validate($document, $sheet);
        yield $document;
    }

    /** @return iterable<string> */
    private function chunks(Sheet $sheet): iterable
    {
        $root = self::name($sheet->getTitle());
        $schema = $sheet->getSchema();
        $attrs = ' xmlns:xsi="' . self::XSI . '"';
        if ($schema !== null) {
            $attrs = ' xmlns="' . self::e($schema['namespace']) . '"' . $attrs;
            if ($schema['location'] !== null) {
                $attrs .= ' xsi:schemaLocation="' . self::e($schema['namespace'] . ' ' . $schema['location']) . '"';
            }
        }
        yield "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<$root$attrs>\n";
        $columns = $sheet->getColumns();
        $names = array_map(fn (array $c): string => self::name($c['name']), $columns);
        $rowElement = self::name($this->xml->rowElement);
        foreach ($sheet->getRows() as $row) {
            $line = "  <$rowElement>";
            foreach ($this->cells->row($columns, $row) as $i => $cell) {
                $line .= $cell->type === CellType::Empty
                    ? "<{$names[$i]} xsi:nil=\"true\"/>"
                    : "<{$names[$i]}>" . self::e(Cells::machine($cell)) . "</{$names[$i]}>";
            }
            yield "$line</$rowElement>\n";
        }
        yield "</$root>\n";
    }

    /** An XML Schema for the sheet: the root, unbounded rows, one nillable element per column. */
    public function xsd(Sheet $sheet): string
    {
        $namespace = $sheet->getSchema()['namespace'] ?? null;
        $target = $namespace !== null ? ' targetNamespace="' . self::e($namespace) . '" xmlns="' . self::e($namespace) . '" elementFormDefault="qualified"' : '';
        $elements = '';
        foreach ($sheet->getColumns() as $column) {
            $type = match ($column['type']) {
                CellType::Number => 'xs:decimal',
                CellType::Bool => 'xs:boolean',
                CellType::Date => 'xs:dateTime',
                default => 'xs:string',
            };
            $elements .= '          <xs:element name="' . self::name($column['name']) . "\" type=\"$type\" nillable=\"true\"/>\n";
        }
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<xs:schema xmlns:xs=\"http://www.w3.org/2001/XMLSchema\"$target>\n"
            . '  <xs:element name="' . self::name($sheet->getTitle()) . "\">\n    <xs:complexType>\n      <xs:sequence>\n"
            . '        <xs:element name="' . self::name($this->xml->rowElement) . "\" minOccurs=\"0\" maxOccurs=\"unbounded\">\n"
            . "          <xs:complexType>\n        <xs:sequence>\n$elements        </xs:sequence>\n          </xs:complexType>\n"
            . "        </xs:element>\n      </xs:sequence>\n    </xs:complexType>\n  </xs:element>\n</xs:schema>\n";
    }

    private function validate(string $document, Sheet $sheet): void
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadXML($document);
        $xsd = $sheet->getSchema()['xsd'] ?? null;
        $valid = $xsd !== null ? $dom->schemaValidate($xsd) : $dom->schemaValidateSource($this->xsd($sheet));
        $errors = array_map(fn (\LibXMLError $e): string => trim($e->message), libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$valid) {
            throw new RuntimeException("XML export '{$sheet->getFilename()}' does not match its schema: " . implode(' ', $errors));
        }
    }

    /** A usable element name: letters, digits, '_', '-', '.'; anything else becomes '_'. */
    public static function name(string $text): string
    {
        $name = preg_replace('/[^A-Za-z0-9_.\-]/', '_', $text) ?? '_';
        return preg_match('/^[A-Za-z_]/', $name) ? $name : "_$name";
    }

    private static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
