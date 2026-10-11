<?php

namespace Waypoint\Export\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Waypoint\Export\Writers;
use Waypoint\Export\Writers\{CsvWriter, JsonLinesWriter};

final class WritersTest extends TestCase
{
    public function testLooksUpByExtensionCaseInsensitively(): void
    {
        $csv = new CsvWriter(Support::cells(), Support::options());
        $writers = (new Writers())->add($csv)->add(new JsonLinesWriter(Support::cells()));

        $this->assertSame($csv, $writers->for('CSV'));
        $this->assertTrue($writers->has('jsonl'));
        $this->assertFalse($writers->has('pdf'));
        $this->assertSame(['csv', 'jsonl'], array_keys($writers->all()));
    }

    public function testAnUnknownFormatNamesTheInstalledOnes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("No export writer for 'pdf'; installed: csv.");
        (new Writers())->add(new CsvWriter(Support::cells(), Support::options()))->for('pdf');
    }
}
