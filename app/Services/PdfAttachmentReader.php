<?php

namespace App\Services;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Turns a PDF into something an assistant can read: page images when a
 * renderer is installed on the server (Imagick, pdftoppm or Ghostscript),
 * otherwise the text layer (pdftotext). Used by the get-attachment MCP tool.
 */
class PdfAttachmentReader
{
    public const MAX_PAGES = 4;

    /**
     * @return array{images: list<string>, text: string|null, pages: int|null}
     *                                                                         images are PNG bytes, one per page, at most MAX_PAGES; text is set only when no page could be drawn
     */
    public function read(string $pdfBytes): array
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'bassist-pdf-'.bin2hex(random_bytes(6));
        mkdir($dir, 0700, true);
        $pdf = $dir.DIRECTORY_SEPARATOR.'in.pdf';
        file_put_contents($pdf, $pdfBytes);

        try {
            $images = $this->renderImages($pdf, $dir);

            return [
                'images' => $images,
                'text' => $images === [] ? $this->extractText($pdf) : null,
                'pages' => $this->pageCount($pdf),
            ];
        } finally {
            foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }
    }

    /**
     * @return list<string>
     */
    protected function renderImages(string $pdf, string $dir): array
    {
        $last = self::MAX_PAGES;
        $finder = new ExecutableFinder;

        if (extension_loaded('imagick')) {
            try {
                $images = [];
                for ($page = 0; $page < $last; $page++) {
                    $im = new \Imagick;
                    $im->setResolution(110, 110);
                    $im->readImage($pdf.'['.$page.']');
                    $im->setImageBackgroundColor('white');
                    $im = $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                    $im->setImageFormat('png');
                    $images[] = $im->getImageBlob();
                }

                return $images;
            } catch (\Throwable) {
                if (isset($images) && $images !== []) {
                    return $images;
                }
            }
        }

        $commands = [];
        if (($pdftoppm = $finder->find('pdftoppm')) !== null) {
            $commands[] = [$pdftoppm, '-png', '-r', '110', '-l', (string) $last, $pdf, $dir.DIRECTORY_SEPARATOR.'p'];
        }
        foreach (['gs', 'gswin64c', 'gswin32c'] as $gs) {
            if (($path = $finder->find($gs)) !== null) {
                $commands[] = [$path, '-q', '-dNOPAUSE', '-dBATCH', '-sDEVICE=png16m', '-r110', '-dLastPage='.$last, '-sOutputFile='.$dir.DIRECTORY_SEPARATOR.'p-%d.png', $pdf];
                break;
            }
        }

        foreach ($commands as $command) {
            $process = new Process($command);
            $process->setTimeout(60);
            $process->run();

            $files = glob($dir.DIRECTORY_SEPARATOR.'p*.png') ?: [];
            sort($files, SORT_NATURAL);
            if ($process->isSuccessful() && $files !== []) {
                return array_map(fn (string $file) => (string) file_get_contents($file), array_slice($files, 0, $last));
            }
        }

        return [];
    }

    protected function extractText(string $pdf): ?string
    {
        $path = (new ExecutableFinder)->find('pdftotext');
        if ($path === null) {
            return null;
        }

        $process = new Process([$path, '-layout', '-l', (string) self::MAX_PAGES, $pdf, '-']);
        $process->setTimeout(30);
        $process->run();

        $text = trim($process->getOutput());

        return $process->isSuccessful() && $text !== '' ? $text : null;
    }

    protected function pageCount(string $pdf): ?int
    {
        $contents = (string) @file_get_contents($pdf);

        return preg_match_all('/\/Type\s*\/Page[^s]/', $contents) ?: null;
    }
}
