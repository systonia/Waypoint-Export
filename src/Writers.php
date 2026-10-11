<?php

namespace Waypoint\Export;

use RuntimeException;

/** The registered writers by extension; ExportPlugin puts one in the container. */
final class Writers
{
    /** @var array<string, Writer> */
    private array $writers = [];

    public function add(Writer $writer): self
    {
        $this->writers[strtolower($writer->extension())] = $writer;
        return $this;
    }

    public function for(string $format): Writer
    {
        return $this->writers[strtolower($format)]
            ?? throw new RuntimeException("No export writer for '$format'; installed: " . implode(', ', array_keys($this->writers)) . '.');
    }

    public function has(string $format): bool
    {
        return isset($this->writers[strtolower($format)]);
    }

    /** @return array<string, Writer> extension => writer */
    public function all(): array
    {
        return $this->writers;
    }
}
