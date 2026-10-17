---
title: Tables in views
description: Render a Sheet as an HTML table inside a Waypoint view with $this->sheet().
order: 60
---

The plugin adds the view helper `sheet`: in a view or layout, `$this->sheet($sheet)` returns the sheet as a bare HTML `<table>` (a `Waypoint\Html`), the same table the `.html` download contains, without the page around it. One sheet definition then serves the page and the download.

```php
#[Get('/admin/report')]
public function report(): View
{
    return new View('Report', ['users' => $this->usersSheet('html')]);
}

#[Get('/admin/users.{format}')]
public function users(string $format): Sheet
{
    return $this->usersSheet($format);
}

private function usersSheet(string $format): Sheet
{
    return Sheet::file("users.$format")
        ->title('Users')
        ->columns(['id' => 'ID', 'email' => 'Email'])
        ->column('name', 'Name', fn(User $u): string => $u->fullName())
        ->columns(['balance' => 'Balance', 'registered' => 'Registered'])
        ->rows($this->users->all());
}
```

`views/Report.php`:

```html
<h1>Users</h1>
{{ $this->sheet($model['users']) }}
<p><a href="/admin/users.xlsx">Download as XLSX</a> · <a href="/admin/users.csv">CSV</a></p>
```

The output:

```html
<table class="wp-sheet"><caption>Users</caption><thead><tr><th scope="col">ID</th><th scope="col">Email</th><th scope="col">Name</th><th scope="col">Balance</th><th scope="col">Registered</th></tr></thead><tbody>
<tr><td class="wp-sheet-num">1</td><td>ada@example.com</td><td>Ada Lovelace</td><td class="wp-sheet-num">1234,5</td><td>01.03.2026 09:15</td></tr>
<tr><td class="wp-sheet-num">2</td><td>grace@example.com</td><td>Grace Hopper</td><td class="wp-sheet-num">-20</td><td>17.05.2026 14:00</td></tr>
</tbody></table>
```

- The format of the sheet does not matter to the helper; `Sheet::file('users.html')` is just the natural name.
- Labels, the caption and every cell are HTML-escaped by the helper. It returns the table as a `Waypoint\Html`, which `{{ }}` prints as it is.
- Cells hold the text representation: `decimalSeparator`, `dateFormat` and `booleans` from [Configuration](configuration.md) apply.
- The table has no styles of its own. Number cells carry `wp-sheet-num`, header cells `scope="col"`.

## Styling

A view's sibling stylesheet is scoped to the view, so `views/Report.css` can style the table:

```css
.wp-sheet { border-collapse: collapse; }
.wp-sheet caption { text-align: left; font-weight: 600; }
.wp-sheet th, .wp-sheet td { padding: 4px 8px; border-bottom: 1px solid #ddd; text-align: left; }
.wp-sheet td.wp-sheet-num { text-align: right; font-variant-numeric: tabular-nums; }
```

The layout includes it with `{{ $this->assetTags() }}`, and the element that should receive it carries `{{ $this->scopeAttribute() }}`, as for any Waypoint view.

## Large tables

The helper builds the whole table as one string before the view continues. For a page, keep the rows to what a person reads (the latest hundred orders) and link the download for the rest:

```php
return new View('Orders', [
    'latest' => $this->ordersSheet('html')->rows($this->orders->latest(100)),
]);
```

See also: [Formats and cell types](formats.md#html), [`HtmlWriter`](../reference/Waypoint/Export/Writers/HtmlWriter.md).
