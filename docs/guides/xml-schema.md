---
title: XML with a schema
description: Give an XML export a namespace and schema location, serve the generated XSD, and validate every document.
order: 40
---

An XML export is plain XML until the sheet names a schema. `schema()` sets the document's namespace and, optionally, the `xsi:schemaLocation` a recipient uses to find the XSD:

```php
private function salesSheet(): Sheet
{
    return Sheet::file('sales.xml')
        ->title('sales')
        ->schema('urn:sonnenkorn:sales', 'sales.xsd')
        ->columnsOf(SalesRow::class);
}

#[Get('/sales.xml')]
#[Role('owner')]
public function sales(): Sheet
{
    return $this->salesSheet()->rows($this->salesRows());
}
```

With `SalesRow` from [Row classes](row-classes.md), and `salesRows()` running the query shown there:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sales xmlns="urn:sonnenkorn:sales" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:sonnenkorn:sales sales.xsd">
  <row><day>2026-10-16T00:00:00+02:00</day><product>Lye pretzel</product><quantity>48</quantity><revenue>57.6</revenue></row>
  <row><day>2026-10-16T00:00:00+02:00</day><product>Butter croissant</product><quantity>31</quantity><revenue>55.8</revenue></row>
</sales>
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
use Waypoint\Attributes\{Authenticated, Controller, FileFormatter, Get, Inject};
use Waypoint\Export\{Sheet, Writers};
use Waypoint\Export\Writers\XmlWriter;

#[Controller('/backoffice')]
#[Authenticated]
final class SalesExportController
{
    #[Inject]
    private Writers $writers;

    #[Get('/sales.xsd')]
    #[FileFormatter(
        mimetype: 'application/xml'
    )]
    public function schema(): string
    {
        $xml = $this->writers->for('xml');
        assert($xml instanceof XmlWriter);
        return $xml->xsd($this->salesSheet());
    }
}
```

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" targetNamespace="urn:sonnenkorn:sales" xmlns="urn:sonnenkorn:sales" elementFormDefault="qualified">
  <xs:element name="sales">
    <xs:complexType>
      <xs:sequence>
        <xs:element name="row" minOccurs="0" maxOccurs="unbounded">
          <xs:complexType>
        <xs:sequence>
          <xs:element name="day" type="xs:dateTime" nillable="true"/>
          <xs:element name="product" type="xs:string" nillable="true"/>
          <xs:element name="quantity" type="xs:decimal" nillable="true"/>
          <xs:element name="revenue" type="xs:decimal" nillable="true"/>
        </xs:sequence>
          </xs:complexType>
        </xs:element>
      </xs:sequence>
    </xs:complexType>
  </xs:element>
</xs:schema>
```

The schema only needs the columns, so `salesSheet()` without rows serves both routes. Declaring `type` on the columns is what makes the XSD say more than `xs:string`; the XML itself looks the same either way.

## Validating every document

With validation on, the XML writer checks each document against its schema before it is sent:

```php
$app->configure(function (ExportOptions $options, Environment $env) {
    $options->xml->validate = $env->isDev();
});
```

Without a schema file the generated XSD is used. For a schema you were given by the recipient, pass its path as the third argument. The bakery's flour order to its mill, in the mill's format:

```php
Sheet::file('flour-order.xml')
    ->title('order')
    ->schema('urn:mill:orders', 'https://mill.test/orders.xsd', xsd: __DIR__ . '/schemas/mill-orders.xsd')
    ->columnsOf(FlourOrderRow::class)
    ->rows($rows);
```

A document that does not match throws a `RuntimeException` with libxml's messages, `XML export 'flour-order.xml' does not match its schema: …`; the route answers 500 and the message goes to the log. While validating, the writer builds the whole document in memory before it sends anything, so validation fits development and CI rather than large production exports.

## Element names

The root, the row element and the column elements are made valid XML names: letters, digits, `_`, `-` and `.` stay, everything else becomes `_`, and a leading `_` is added when the name does not start with a letter or `_`. The row element is `row` unless [`xml->rowElement`](configuration.md#xml) says otherwise; it applies to the documents and the generated XSD alike.

See also: [Formats and cell types](formats.md#xml), [`XmlWriter`](../reference/Waypoint/Export/Writers/XmlWriter.md).
