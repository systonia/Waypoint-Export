---
title: XML with a schema
description: Give an XML export a namespace and schema location, serve the generated XSD, and validate every document.
order: 40
---

An XML export is plain XML until the sheet names a schema. `schema()` sets the document's namespace and, optionally, the `xsi:schemaLocation` a recipient uses to find the XSD:

```php
private function customerSheet(): Sheet
{
    return Sheet::file('customers.xml')
        ->title('customers')
        ->schema('urn:example:customers', 'customers.xsd')
        ->columnsOf(CustomerRow::class);
}

#[Get('/customers.xml')]
public function customers(): Sheet
{
    return $this->customerSheet()->rows(array_map(CustomerRow::of(...), $this->users->all()));
}
```

With `CustomerRow` from [Row classes](row-classes.md):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<customers xmlns="urn:example:customers" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:example:customers customers.xsd">
  <row><customerNo>1</customerNo><email>ada@example.com</email><balance>1234.5</balance><registered>2026-03-01T09:15:00+00:00</registered></row>
  <row><customerNo>2</customerNo><email>grace@example.com</email><balance>-20</balance><registered>2026-05-17T14:00:00+00:00</registered></row>
</customers>
```

## The generated XSD

[`XmlWriter::xsd()`](../reference/Waypoint/Export/Writers/XmlWriter.md#xsd) derives a schema from a sheet's columns: the root element named after the title, any number of row elements, and one nillable element per column, in column order. The element type comes from the column's declared `type`:

| `type` | XSD type |
|---|---|
| `CellType::Number` | `xs:decimal` |
| `CellType::Bool` | `xs:boolean` |
| `CellType::Date` | `xs:dateTime` |
| anything else, or none | `xs:string` |

A route next to the export can serve it. The XML writer comes from the [`Writers`](../reference/Waypoint/Export/Writers.md) service the plugin puts into the container:

```php
use Waypoint\Attributes\{Controller, FileFormatter, Get, Inject};
use Waypoint\Export\{Sheet, Writers};
use Waypoint\Export\Writers\XmlWriter;

#[Controller('/admin')]
final class ExportController
{
    #[Inject]
    private Writers $writers;

    #[Get('/customers.xsd')]
    #[FileFormatter(mimetype: 'application/xml')]
    public function schema(): string
    {
        $xml = $this->writers->for('xml');
        assert($xml instanceof XmlWriter);
        return $xml->xsd($this->customerSheet());
    }
}
```

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" targetNamespace="urn:example:customers" xmlns="urn:example:customers" elementFormDefault="qualified">
  <xs:element name="customers">
    <xs:complexType>
      <xs:sequence>
        <xs:element name="row" minOccurs="0" maxOccurs="unbounded">
          <xs:complexType>
        <xs:sequence>
          <xs:element name="customerNo" type="xs:decimal" nillable="true"/>
          <xs:element name="email" type="xs:string" nillable="true"/>
          <xs:element name="balance" type="xs:decimal" nillable="true"/>
          <xs:element name="registered" type="xs:dateTime" nillable="true"/>
        </xs:sequence>
          </xs:complexType>
        </xs:element>
      </xs:sequence>
    </xs:complexType>
  </xs:element>
</xs:schema>
```

The schema only needs the columns, so `customerSheet()` without rows serves both routes. Declaring `type` on the columns is what makes the XSD say more than `xs:string`; the XML itself looks the same either way.

## Validating every document

With validation on, the XML writer checks each document against its schema before it is sent:

```php
$app->configure(function (ExportOptions $options, Environment $env) {
    $options->xml->validate = $env->isDev();
});
```

Without a schema file the generated XSD is used. For a schema you were given by the recipient, pass its path as the third argument:

```php
Sheet::file('lieferung.xml')
    ->title('lieferung')
    ->schema('urn:partner:lieferung', 'https://partner.example/lieferung.xsd', xsd: __DIR__ . '/schemas/lieferung.xsd')
    ->columnsOf(LieferungRow::class)
    ->rows($rows);
```

A document that does not match throws a `RuntimeException` with libxml's messages, `XML export 'lieferung.xml' does not match its schema: …`; the route answers 500 and the message goes to the log. While validating, the writer builds the whole document in memory before it sends anything, so validation fits development and CI rather than large production exports.

## Element names

The root, the row element and the column elements are made valid XML names: letters, digits, `_`, `-` and `.` stay, everything else becomes `_`, and a leading `_` is added when the name does not start with a letter or `_`. The row element is `row` unless [`xml->rowElement`](configuration.md#xml) says otherwise; it applies to the documents and the generated XSD alike.

See also: [Formats and cell types](formats.md#xml), [`XmlWriter`](../reference/Waypoint/Export/Writers/XmlWriter.md).
