<?php

namespace Waypoint\Export\Tests;

use Waypoint\Export\{Cells, ExportOptions, ExportPlugin, Writers};
use Waypoint\OpenAPI\OpenAPIGenerator;
use Waypoint\Options\RendererOptions;
use Waypoint\Testing\TestCase;

final class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = $this->app();
        $app->plugin(new ExportPlugin());
        $app->configure(fn (ExportOptions $o) => $o->csv->encoding = 'UTF-8');
        $app->configure(fn (RendererOptions $o) => $o->directory = __DIR__ . '/views');
        $app->attach([ExportController::class]);
    }

    public function testASheetReturnedFromARouteIsADownloadInTheFormatOfItsExtension(): void
    {
        $this->get('/users.csv')
            ->assertOk()
            ->assertContentType('text/csv')
            ->assertHeader('Content-Disposition', 'attachment; filename="users.csv"; filename*=UTF-8\'\'users.csv')
            ->assertHeader('Cache-Control', 'no-store')
            ->assertBody("\xEF\xBB\xBFID;Name\r\n1;Ada\r\n2;Grace\r\n");

        $xlsx = $this->get('/users.xlsx')->assertOk()->assertContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $xlsx->body());
    }

    public function testAnUnknownFormatIsAClearError(): void
    {
        // The message itself is covered in WritersTest; the app's handler keeps internals out of the response.
        $this->get('/users.pdf')->assertStatus(500);
    }

    public function testTheSpecListsEveryInstalledContentType(): void
    {
        $paths = (new OpenAPIGenerator($this->app()->getRouter()))->generate()['paths'];

        $this->assertSame(
            ['text/csv', 'application/x-ndjson', 'text/html', 'application/xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.oasis.opendocument.spreadsheet'],
            array_keys($paths['/users.csv']['get']['responses']['200']['content'])
        );
        $this->assertSame(['application/json'], array_keys($paths['/plain']['get']['responses']['200']['content']));
    }

    public function testTheViewHelperRendersAnInlineTableAndServicesAreInTheContainer(): void
    {
        $this->get('/page')->assertOk()->assertSee('<div><table class="wp-sheet"><caption>inline</caption>')->assertSee('<td class="wp-sheet-num">2</td><td>Grace</td>')->assertDontSee('<html');

        $container = $this->app()->getContainer();
        $this->assertInstanceOf(Cells::class, $container->get(Cells::class));
        $this->assertTrue($container->get(Writers::class)->has('ods'));
    }
}
