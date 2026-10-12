<?php

namespace App\Mcp\Tools;

use App\Services\AttachmentService;
use App\Services\EntityRecordService;
use App\Services\PdfAttachmentReader;
use App\Support\EntityAccess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetAttachmentTool extends BAssistTool
{
    /** Larger images are refused rather than sent whole; the file is still downloadable in the web UI. */
    protected const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    protected const MAX_HTML_CHARS = 150000;

    protected const IMAGE_MIMES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    protected string $name = 'get-attachment';

    protected string $title = 'Get an attachment';

    protected string $description = 'The content of one file attached to a record. Images (png, jpg, gif, webp) come back as an image you can look at; a PDF as images of its first pages (or, if the server cannot draw PDFs, its text only); an HTML file as its markup with scripts, styles and embedded images removed (the best source for exact labels, field types and nesting); text files (txt, md, csv, puml, salt, feature) as text. Find attachment ids in the attachments list of get-record. Use it to convert a screenshot attached to a Screen (UI design) into its elements; for ordinary design work read the Screen salt instead of the image.';

    public function handle(Request $request, AttachmentService $attachments, EntityRecordService $records, PdfAttachmentReader $pdfs): Response|ResponseFactory
    {
        $validated = $request->validate([
            'entity' => ['required', 'string', 'max:100'],
            'id' => ['required', 'integer'],
            'attachment_id' => ['required', 'integer'],
        ]);

        try {
            $model = $records->resolve($validated['entity']);
            EntityAccess::authorize(auth()->user(), $model, EntityAccess::VIEW);

            $found = $attachments->content($model, (int) $validated['id'], (int) $validated['attachment_id']);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return Response::error($e->getMessage() !== '' ? $e->getMessage() : 'Not found.');
        }

        $mime = strtolower((string) $found['attachment']->mime);
        $name = $found['attachment']->original_name;

        if (in_array($mime, self::IMAGE_MIMES, true)) {
            if (strlen($found['contents']) > self::MAX_IMAGE_BYTES) {
                return Response::error("{$name} is larger than 5 MB. Ask the person to attach a smaller screenshot.");
            }

            return Response::make([
                Response::text("Attachment {$name} ({$mime})."),
                Response::image($found['contents'], $mime),
            ]);
        }

        if ($mime === 'text/html' || in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['html', 'htm'], true)) {
            return Response::text($this->html($name, $found['contents']));
        }

        if (str_starts_with($mime, 'text/') || in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['txt', 'md', 'csv', 'puml', 'salt', 'feature'], true)) {
            return Response::text($found['contents']);
        }

        if ($mime === 'application/pdf' || strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'pdf') {
            return $this->pdf($name, $found['contents'], $pdfs);
        }

        return Response::error("{$name} ({$mime}) cannot be read through MCP. Only images, PDFs and text files can.");
    }

    /**
     * The structure of an HTML mockup without the weight: scripts, style blocks,
     * comments and embedded data are removed, so the tags, labels, field types,
     * placeholders and nesting that a design is read from remain.
     */
    protected function html(string $name, string $html): string
    {
        $clean = preg_replace([
            '#<!--.*?-->#s',
            '#<(script|style|noscript|template)\b.*?</\1\s*>#is',
            '#data:[a-z/+.-]+;base64,[A-Za-z0-9+/=\s]+#i',
            '#\s+style="[^"]*"#i',
            '#>\s+<#',
            '#[ \t]{2,}#',
        ], ['', '', 'data:...', '', '><', ' '], $html) ?? $html;

        $clean = trim($clean);
        $note = "Attachment {$name} (HTML): scripts, style blocks, inline styles, comments and embedded images removed.";

        if (strlen($clean) > self::MAX_HTML_CHARS) {
            $clean = substr($clean, 0, self::MAX_HTML_CHARS);
            $note .= ' Cut at '.number_format(self::MAX_HTML_CHARS).' characters; the rest of the file is not shown.';
        }

        return $note."\n\n".$clean;
    }

    protected function pdf(string $name, string $contents, PdfAttachmentReader $reader): Response|ResponseFactory
    {
        $read = $reader->read($contents);

        if ($read['images'] !== []) {
            $total = $read['pages'];
            $shown = count($read['images']);
            $note = "Attachment {$name} (PDF), first {$shown} page".($shown === 1 ? '' : 's').($total !== null && $total > $shown ? " of {$total}" : '').' drawn as images.';

            return Response::make([
                Response::text($note),
                ...array_map(fn (string $png) => Response::image($png, 'image/png'), $read['images']),
            ]);
        }

        if ($read['text'] !== null) {
            return Response::text("Attachment {$name} (PDF). This server cannot draw PDF pages (no Imagick, pdftoppm or Ghostscript), so this is only its text layer; the layout is lost. Ask the person to attach a screenshot (png or jpg) for a faithful conversion.\n\n".$read['text']);
        }

        return Response::error("{$name} is a PDF and this server cannot draw or read it (no Imagick, pdftoppm, Ghostscript or pdftotext installed). Ask the person to attach a screenshot (png or jpg) instead.");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entity' => $schema->string()->description('Entity name of the record the file is attached to, for example Screen.')->required(),
            'id' => $schema->integer()->description('Record id.')->required(),
            'attachment_id' => $schema->integer()->description('Attachment id from the attachments list of get-record.')->required(),
        ];
    }
}
