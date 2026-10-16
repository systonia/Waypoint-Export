<?php

namespace Waypoint\Export;

/** One output format. Register extra writers from another plugin through Writers::add() in boot(). */
interface Writer
{
    /** The file extension the writer answers to; `Sheet::file('x.<extension>')` selects it. */
    public function extension(): string;

    /** The Content-Type header of the download, with a charset for text formats. */
    public function contentType(): string;

    /**
     * The file, chunk by chunk: a generator, so a large export never sits in memory twice.
     * @return iterable<string>
     */
    public function stream(Sheet $sheet): iterable;
}
