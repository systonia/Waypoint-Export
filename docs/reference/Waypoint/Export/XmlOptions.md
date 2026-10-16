---
title: "XmlOptions"
description: "`ExportOptions::$xml`."
order: 100
generated: true
---

`Waypoint\Export\XmlOptions` · class · namespace [`Waypoint\Export`](index.md)

```php
class XmlOptions
```

`ExportOptions::$xml`.

## Properties

### $validate

```php
public bool $validate = false;
```

Validate every document against its XSD (the one given to Sheet::schema(), else the generated one) and fail loudly; for dev and tests.

### $rowElement

```php
public string $rowElement = 'row';
```

Element name of one row.
