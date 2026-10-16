---
title: Guides
description: One part of Waypoint Export per page, with examples.
order: 10
---

Each guide covers one part of the plugin. The examples assume the plugin is registered with `$app->plugin(new ExportPlugin())`; the [reference](../reference/index.md) lists every class and method.

Exports:

- [Building a sheet](sheets.md): `Sheet::file()`, columns, closures, rows, generators, titles
- [Row classes](row-classes.md): `#[Column]` on a class, `columnsOf()`, column meta
- [Formats and cell types](formats.md): what each writer makes of numbers, dates, booleans and text
- [XML with a schema](xml-schema.md): namespaces, the generated XSD, validation
- [Configuration](configuration.md): `ExportOptions`, CSV and XML settings
- [Tables in views](views.md): `$this->sheet()`

Extending and checking:

- [Adding a format](custom-formats.md): a `Writer` from another plugin
- [Testing exports](testing.md): routes and writers in PHPUnit
- [Errors and limits](edge-cases.md): file names, unknown formats, memory, encodings
