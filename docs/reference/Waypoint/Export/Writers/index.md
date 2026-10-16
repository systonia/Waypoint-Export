---
title: "Waypoint\\Export\\Writers"
description: "The classes, interfaces, traits and enums in Waypoint\\Export\\Writers."
order: 200
generated: true
---

## Types

- [`CsvWriter`](CsvWriter.md) · final class — CSV in the configured delimiter, line ending and encoding; a BOM when UTF-8.
- [`HtmlWriter`](HtmlWriter.md) · final class — A standalone HTML page with one table; fragment() gives the bare `<table>` for `$this->sheet()` in views.
- [`JsonLinesWriter`](JsonLinesWriter.md) · final class — One JSON object per line, keyed by column name, with native types (numbers, booleans, null, ISO 8601 dates).
- [`OdsWriter`](OdsWriter.md) · final class — A minimal OpenDocument spreadsheet: stored mimetype first, manifest, one table in content.xml with typed cells.
- [`XlsxWriter`](XlsxWriter.md) · final class — A minimal, valid OOXML workbook: one sheet, inline strings, real numbers and booleans, dates as serials with a number format, bold header.
- [`XmlWriter`](XmlWriter.md) · final class — `<title xmlns="..."><row><col>...</col></row>...</title>`; empty cells are `xsi:nil`.
- [`ZipPackage`](ZipPackage.md) · final class — A zip built in a temp file from strings and temp files, streamed out in chunks; shared by XLSX and ODS.
