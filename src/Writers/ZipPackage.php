<?php

namespace Waypoint\Export\Writers;

use RuntimeException;
use ZipArchive;

/** A zip built in a temp file from strings and temp files, streamed out in chunks; shared by XLSX and ODS. */
final class ZipPackage
{
    private readonly ZipArchive $zip;
    private readonly string $path;

    /** @var list<string> temp files to delete after streaming */
    private array $temps = [];

    /** Opens a new archive in a temp file. */
    public function __construct()
    {
        $this->path = self::temp();
        $this->zip = new ZipArchive();
        if ($this->zip->open($this->path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create zip archive at {$this->path}."); // @codeCoverageIgnore
        }
    }

    /** Adds an entry from a string; $store keeps it uncompressed (ODS needs that for `mimetype`). */
    public function addString(string $name, string $content, bool $store = false): self
    {
        $this->zip->addFromString($name, $content);
        if ($store) {
            $this->zip->setCompressionName($name, ZipArchive::CM_STORE);
        }
        return $this;
    }

    /** A file written through tempFile(); deleted once the archive has been streamed. */
    public function addFile(string $name, string $path): self
    {
        $this->zip->addFile($path, $name);
        return $this;
    }

    /** A scratch file a writer can append the big part (the sheet XML) to. */
    public function tempFile(): string
    {
        return $this->temps[] = self::temp();
    }

    /** @return iterable<string> */
    public function stream(): iterable
    {
        $this->zip->close();
        $handle = fopen($this->path, 'rb');
        if ($handle !== false) {
            while (!feof($handle)) {
                yield (string) fread($handle, 65536);
            }
            fclose($handle);
        }
        foreach ([$this->path, ...$this->temps] as $file) {
            @unlink($file);
        }
    }

    private static function temp(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wpx');
        if ($path === false) {
            throw new RuntimeException('Cannot create a temp file for the export.'); // @codeCoverageIgnore
        }
        return $path;
    }
}
