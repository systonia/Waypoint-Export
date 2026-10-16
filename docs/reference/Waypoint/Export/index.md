---
title: "Waypoint\\Export"
description: "The classes, interfaces, traits and enums in Waypoint\\Export."
order: 200
generated: true
---

## Namespaces

- [`Waypoint\Export\Writers`](Writers/index.md)

## Types

- [`Cell`](Cell.md) · final class: A typed cell: Text carries a string, Number an int|float, Bool a bool, Date a DateTimeInterface, Empty null.
- [`Cells`](Cells.md) · final class: The one place that reads a row and types a value.
- [`CellType`](CellType.md) · enum: What a cell holds; each Writer decides how to represent it (a real number in XLSX, `1234,5` in CSV, `1234.5` in JSONL).
- [`Column`](Column.md) · final class: Marks a public property of a row class as an export column; `Sheet::columnsOf(RowClass::class)` collects them in declaration order.
- [`CsvOptions`](CsvOptions.md) · class: `ExportOptions::$csv`.
- [`ExportOptions`](ExportOptions.md) · class: Configure with `$app->configure(function (ExportOptions $opts) { ... })`.
- [`ExportPlugin`](ExportPlugin.md) · final class: Register with `$app->plugin(new ExportPlugin())`.
- [`Sheet`](Sheet.md) · final class: A downloadable table.
- [`Writer`](Writer.md) · interface: One output format.
- [`Writers`](Writers.md) · final class: The registered writers by extension; ExportPlugin puts one in the container.
- [`XmlOptions`](XmlOptions.md) · class: `ExportOptions::$xml`.
