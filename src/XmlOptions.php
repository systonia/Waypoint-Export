<?php

namespace Waypoint\Export;

/** `ExportOptions::$xml`. */
class XmlOptions
{
    /** Validate every document against its XSD (the one given to Sheet::schema(), else the generated one) and fail loudly; for dev and tests. */
    public bool $validate = false;

    /** Element name of one row. */
    public string $rowElement = 'row';
}
