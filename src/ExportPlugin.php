<?php

namespace Waypoint\Export;

use Waypoint\Container;
use Waypoint\Export\Writers\{CsvWriter, HtmlWriter, JsonLinesWriter, OdsWriter, XlsxWriter, XmlWriter};
use Waypoint\Http\{Request, Response};
use Waypoint\Plugin\{OpenApiExtension, PluginBase, Renderer, ViewHelper};

/**
 * Register with `$app->plugin(new ExportPlugin())`. A Sheet returned from a
 * route becomes a download in the format its extension names (csv, jsonl,
 * html, xml, xlsx, ods built in; more through Writers::add() from another
 * plugin), the spec lists every installed content type, and templates get
 * `$this->sheet($sheet)` for an inline HTML table.
 */
final class ExportPlugin extends PluginBase implements Renderer, OpenApiExtension, ViewHelper
{
    private Writers $writers;
    private HtmlWriter $html;

    public function name(): string
    {
        return 'export';
    }

    public function boot(Container $container): void
    {
        $options = $container->get(ExportOptions::class);
        $cells = new Cells($options);
        $this->html = new HtmlWriter($cells);
        $this->writers = (new Writers())
            ->add(new CsvWriter($cells, $options))
            ->add(new JsonLinesWriter($cells))
            ->add($this->html)
            ->add(new XmlWriter($cells, $options))
            ->add(new XlsxWriter($cells, $options))
            ->add(new OdsWriter($cells));
        $container->set($cells);
        $container->set($this->writers);
    }

    public function hooks(): array
    {
        return [$this];
    }

    public function supports(mixed $result): bool
    {
        return $result instanceof Sheet;
    }

    public function render(mixed $result, Request $req, Response $res): void
    {
        if (!$result instanceof Sheet) {
            return;
        }
        $writer = $this->writers->for($result->getFormat());
        $res->withHeader('Content-Type', $writer->contentType())
            ->withHeader('Content-Disposition', 'attachment; filename="' . $result->getFilename() . '"; filename*=UTF-8\'\'' . rawurlencode($result->getFilename()))
            ->withHeader('Cache-Control', 'no-store');
        foreach ($writer->stream($result) as $chunk) {
            $res->write($chunk);
        }
    }

    public function helpers(): array
    {
        return ['sheet' => fn(Sheet $sheet): string => implode('', iterator_to_array($this->html->fragment($sheet), false))];
    }

    public function describeParameter(array $parameter): ?array
    {
        return null;
    }

    public function describeResult(string $returnType): ?array
    {
        if ($returnType !== Sheet::class) {
            return null;
        }
        $content = [];
        foreach ($this->writers->all() as $extension => $writer) {
            $type = trim(explode(';', $writer->contentType())[0]);
            $content[$type] = ['schema' => ['type' => 'string', 'description' => "File download (.$extension, Content-Disposition: attachment)"]];
        }
        return $content;
    }
}
