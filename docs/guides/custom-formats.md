---
title: Adding a format
description: Write a Writer for another format and register it from your own plugin.
order: 70
---

A format is a class implementing [`Writer`](../reference/Waypoint/Export/Writer.md):

```php
interface Writer
{
    public function extension(): string;       // 'md': Sheet::file('x.md') selects it
    public function contentType(): string;     // the Content-Type header
    /** @return iterable<string> */
    public function stream(Sheet $sheet): iterable;   // the file, chunk by chunk
}
```

The export plugin keeps its writers in a [`Writers`](../reference/Waypoint/Export/Writers.md) registry and puts it into the container, together with the [`Cells`](../reference/Waypoint/Export/Cells.md) that types values under the app's `ExportOptions`. Another plugin adds its writer in `boot()`, which gets both as parameters.

## A Markdown table writer

```php
<?php

namespace Acme\Export;

use Waypoint\Export\{Cells, ExportPlugin, Sheet, Writer, Writers};
use Waypoint\Plugin\PluginBase;

/** A Markdown table: header row, separator, one line per row. */
final class MarkdownWriter implements Writer
{
    public function __construct(private readonly Cells $cells)
    {
    }

    public function extension(): string
    {
        return 'md';
    }

    public function contentType(): string
    {
        return 'text/markdown; charset=utf-8';
    }

    public function stream(Sheet $sheet): iterable
    {
        $columns = $sheet->getColumns();
        yield self::line(array_column($columns, 'label'));
        yield self::line(array_fill(0, count($columns), '---'));
        foreach ($sheet->getRows() as $row) {
            yield self::line(array_map($this->cells->text(...), $this->cells->row($columns, $row)));
        }
    }

    /** @param list<string> $cells */
    private static function line(array $cells): string
    {
        return '| ' . implode(' | ', array_map(fn(string $c): string => str_replace(['|', "\n"], ['\|', ' '], $c), $cells)) . " |\n";
    }
}

final class MarkdownExportPlugin extends PluginBase
{
    public function name(): string
    {
        return 'export-markdown';
    }

    public function requires(): array
    {
        return [ExportPlugin::class];
    }

    public function boot(Cells $cells, Writers $writers): void
    {
        $writers->add(new MarkdownWriter($cells));
    }
}
```

Register it after the export plugin:

```php
$app->plugin(new ExportPlugin());
$app->plugin(new MarkdownExportPlugin());
```

Now `Sheet::file('revenue.md')` downloads as

```text
| Month | Net | Paid |
| --- | --- | --- |
| 2026-09 | 18250,75 | 1 |
| 2026-10 | 9100 | 0 |
```

with `Content-Type: text/markdown; charset=utf-8`, the same `Content-Disposition` and `Cache-Control` as every export, and `text/markdown` in the OpenAPI spec of every route that returns a `Sheet`.

- `requires()` makes Waypoint boot the export plugin first, so `Cells` and `Writers` are in the container when `boot()` asks for them.
- The writer gets `Cells` instead of reading rows itself: `row($columns, $row)` reads every column (key, property or closure) and types the values, `text()` gives the representation with the app's decimal separator, date format and booleans, and `Cells::machine()` the locale-free one (`1234.5`, `true`, ISO 8601).
- `add()` registers by the lowercased extension and replaces a writer already registered for it, so a plugin can also replace a built-in format. The same writer class can be added twice under different names, as waypoint-export-fixed does for `txt` and `fixed`.
- If the format needs settings, the plugin has its own Options class and takes it in `boot()` (`boot(Cells $cells, Writers $writers, MarkdownOptions $options)`); it does not change `ExportOptions`.

## Writing the file

`stream()` returns an iterable of strings; a generator is the natural form. Read `getRows()` once, while yielding, so a generator passed to `rows()` is never held as a whole. The other getters:

| Method | |
|---|---|
| `getColumns()` | the columns in order, each `['key', 'label', 'name', 'type', 'value', 'meta']` |
| `getTitle()` | the title, default: the file name without extension |
| `getFilename()`, `getFormat()` | the download name and the writer name |
| `getSchema()` | what `schema()` set, or `null` |

A format packaged as a zip can build on [`ZipPackage`](../reference/Waypoint/Export/Writers/ZipPackage.md), which XLSX and ODS use: `addString()` for small entries, `tempFile()` + `addFile()` for the large one written row by row, `stream()` to yield the archive in 64 KiB chunks and remove the temp files.

## Column meta

A writer that needs per-column settings reads them from the column's meta: attributes next to `#[Column]` on a [row class](row-classes.md), or objects given to `Sheet::meta()`. Meta is keyed by class:

```php
/** Aligns a column in a Markdown table. */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Align
{
    public function __construct(public string $side)
    {
    }
}
```

```php
yield self::line(array_map(fn(array $c): string => match (($c['meta'][Align::class] ?? null)?->side) {
    'right' => '---:',
    'center' => ':---:',
    default => '---',
}, $columns));
```

```php
Sheet::file('revenue.md')
    ->columns(['month' => 'Month'])
    ->column('net', 'Net')
    ->meta(new Align('right'));
```

```text
| Month | Net |
| --- | ---: |
| 2026-09 | 1,5 |
```

The fixed-width writer of waypoint-export-fixed checks every column's meta before its first `yield` and throws an `InvalidArgumentException` naming the column without one, so a missing layout fails the request instead of producing a broken file.

See also: [Testing exports](testing.md), [`Writer`](../reference/Waypoint/Export/Writer.md), [`Writers`](../reference/Waypoint/Export/Writers.md).
