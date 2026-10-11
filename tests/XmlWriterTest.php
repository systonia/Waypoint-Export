<?php

namespace Waypoint\Export\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Waypoint\Export\{Cells, CellType, ExportOptions, Sheet};
use Waypoint\Export\Writers\XmlWriter;

final class XmlWriterTest extends TestCase
{
    private function writer(bool $validate = false, string $rowElement = 'row'): XmlWriter
    {
        $options = Support::options(function (ExportOptions $o) use ($validate, $rowElement) {
            $o->xml->validate = $validate;
            $o->xml->rowElement = $rowElement;
        });
        return new XmlWriter(new Cells($options), $options);
    }

    public function testDocumentWithNamespaceNilAndMachineValues(): void
    {
        $writer = $this->writer();
        $xml = Support::write($writer, Support::sheet('x.xml')->schema('urn:gaiden:users', 'users.xsd'));

        $this->assertSame('application/xml; charset=utf-8', $writer->contentType());
        $this->assertStringStartsWith("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Users xmlns=\"urn:gaiden:users\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:gaiden:users users.xsd\">\n", $xml);
        $this->assertStringContainsString('<row><id>1</id><name>Zoë</name><amount>1234.5</amount><active>true</active><since>2026-09-16T21:30:00+02:00</since><role>admin</role><note xsi:nil="true"/></row>', $xml);
        $this->assertStringContainsString('<name>a;b &quot;q&quot;</name>', $xml);
        $this->assertStringEndsWith("</Users>\n", $xml);
    }

    public function testElementNamesAreSanitized(): void
    {
        $xml = Support::write($this->writer(rowElement: '1 row'), Sheet::file('x.xml')->title('Kunden 2026')->column('a b', 'A', name: '2nd')->rows([['a b' => 1]]));

        $this->assertStringContainsString('<Kunden_2026 ', $xml);
        $this->assertStringContainsString('<_1_row><_2nd>1</_2nd></_1_row>', $xml);
        $this->assertSame('_', XmlWriter::name(''));
    }

    public function testGeneratedXsdDeclaresTypesAndValidatesTheDocument(): void
    {
        $writer = $this->writer(validate: true);
        $sheet = Support::sheet('x.xml')->schema('urn:gaiden:users');
        $sheet->column('id2', 'Id', type: CellType::Number);

        $xsd = $writer->xsd($sheet);
        $this->assertStringContainsString('targetNamespace="urn:gaiden:users"', $xsd);
        $this->assertStringContainsString('<xs:element name="active" type="xs:boolean" nillable="true"/>', $xsd);
        $this->assertStringContainsString('<xs:element name="since" type="xs:dateTime" nillable="true"/>', $xsd);
        $this->assertStringContainsString('<xs:element name="name" type="xs:string" nillable="true"/>', $xsd);

        $this->assertStringContainsString('<Users xmlns="urn:gaiden:users"', Support::write($writer, $sheet), 'a valid document streams through');
        $this->assertStringContainsString('<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">', $this->writer()->xsd(Sheet::file('n.xml')->columns(['a' => 'A'])), 'no namespace: no target');
    }

    public function testValidationFailsLoudlyAgainstAGivenXsd(): void
    {
        $xsd = tempnam(sys_get_temp_dir(), 'xsd');
        file_put_contents($xsd, '<?xml version="1.0"?><xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"><xs:element name="Users"><xs:complexType><xs:sequence>'
            . '<xs:element name="row" maxOccurs="unbounded"><xs:complexType><xs:sequence><xs:element name="id" type="xs:integer"/></xs:sequence></xs:complexType></xs:element>'
            . '</xs:sequence></xs:complexType></xs:element></xs:schema>');
        $sheet = Sheet::file('x.xml')->title('Users')->schema('', xsd: $xsd)->columns(['id' => 'ID', 'extra' => 'Extra'])->rows([['id' => 1, 'extra' => 'not allowed']]);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage("XML export 'x.xml' does not match its schema");
            Support::write($this->writer(validate: true), $sheet);
        } finally {
            unlink($xsd);
        }
    }
}
