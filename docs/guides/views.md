---
title: Tables in views
description: Render a Sheet as an HTML table inside a Waypoint view with $this->sheet().
order: 60
---

The plugin adds the view helper `sheet`: in a view or layout, `$this->sheet($sheet)` returns the sheet as a bare HTML `<table>` (a `Waypoint\Html`), the same table the `.html` download contains, without the page around it. One sheet definition then serves the page and the download.

The Sonnenkorn bakery's sales page shows the orders of the last seven days and links the download of the last 90; both come from the same `sheet()` helper of `BackofficeController` (shortened; the helper is in [Building a sheet](sheets.md#columns)):

```php
#[Get('/sales')]
#[Role('owner')]
public function sales(): View
{
    // ... the charts
    return new View('Backoffice/Sales', new SalesPage(
        revenue: $revenue,
        products: $products,
        latest: $this->sheet('orders.html', $this->orders->recent(7)),
    ), layout: '_Backoffice.php');
}

#[Get('/orders.{format}')]
#[Role('owner')]
public function export(string $format): Sheet
{
    if (!in_array($format, ['csv', 'xlsx'], true)) {
        throw new NotFoundException("No export as $format");
    }
    return $this->sheet("orders.$format", $this->orders->recent(90));
}
```

`SalesPage` is a readonly class with the sheet as `public Sheet $latest`. From `views/Backoffice/Sales.php`:

```html
@model (Bakery\Models\SalesPage)

<section class="mt-10">
    <h2 class="mb-3 flex flex-wrap items-baseline gap-4 text-lg font-semibold">
        {{ $this->t('Orders of the last seven days') }}
        <a href="/backoffice/orders.xlsx" class="text-sm font-normal underline">{{ $this->t('90 days as XLSX') }}</a>
        <a href="/backoffice/orders.csv" class="text-sm font-normal underline">{{ $this->t('as CSV') }}</a>
    </h2>
    {{ $this->sheet($model->latest) }}
</section>
```

The output, shortened to three columns:

```html
<table class="wp-sheet"><caption>Orders</caption><thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Total (€)</th></tr></thead><tbody>
<tr><td>3f9a1c07be</td><td>Lena</td><td class="wp-sheet-num">8,6</td></tr>
<tr><td>81d2e4a90c</td><td>Paul</td><td class="wp-sheet-num">5,2</td></tr>
</tbody></table>
```

- The format of the sheet does not matter to the helper; `Sheet::file('orders.html')` is just the natural name.
- Labels, the caption and every cell are HTML-escaped by the helper. It returns the table as a `Waypoint\Html`, which `{{ }}` prints as it is.
- Cells hold the text representation: `decimalSeparator`, `dateFormat` and `booleans` from [Configuration](configuration.md) apply.
- The table has no styles of its own. Number cells carry `wp-sheet-num`, header cells `scope="col"`.

## Styling

A view's sibling stylesheet is scoped to the view, so `views/Backoffice/Sales.css` could style the table (the bakery styles it with Tailwind in its `assets/app.css` instead):

```css
.wp-sheet { border-collapse: collapse; }
.wp-sheet caption { text-align: left; font-weight: 600; }
.wp-sheet th, .wp-sheet td { padding: 4px 8px; border-bottom: 1px solid #ddd; text-align: left; }
.wp-sheet td.wp-sheet-num { text-align: right; font-variant-numeric: tabular-nums; }
```

The layout includes it with `{{ $this->assetTags() }}`, and the element that should receive it carries `{{ $this->scopeAttribute() }}`, as for any Waypoint view.

## Large tables

The helper builds the whole table as one string before the view continues. For a page, keep the rows to what a person reads and link the download for the rest, as the sales page does with seven days on the page and 90 in the file:

```php
latest: $this->sheet('orders.html', $this->orders->recent(7)),
```

See also: [Formats and cell types](formats.md#html), [`HtmlWriter`](../reference/Waypoint/Export/Writers/HtmlWriter.md).
