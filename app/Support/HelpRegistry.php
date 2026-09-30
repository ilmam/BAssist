<?php

namespace App\Support;

use Illuminate\Support\Str;

class HelpRegistry
{
    /**
     * Resolve the help content key for a CRUD model (plural snake resource name).
     */
    public static function keyForModel(string $model): string
    {
        return CrudEntityRegistry::resourceName(class_basename($model));
    }

    public static function path(string $key): string
    {
        $key = self::normalizeKey($key);

        return resource_path('help/'.$key.'.md');
    }

    public static function exists(string $key): bool
    {
        return is_file(self::path($key));
    }

    public static function existsForModel(string $model): bool
    {
        return self::exists(self::keyForModel($model));
    }

    /**
     * First prose paragraph of a model's help guide as plain text (for empty states / tooltips).
     */
    public static function summaryForModel(string $model, int $limit = 240): ?string
    {
        $path = self::path(self::keyForModel($model));
        if (! is_file($path)) {
            return null;
        }

        $raw = (string) file_get_contents($path);
        [, $markdown] = self::splitFrontMatter($raw);

        foreach (preg_split('/\R{2,}/', trim($markdown)) ?: [] as $block) {
            $block = trim($block);
            if ($block === '' || preg_match('/^(#|>|-|\*|\d+\.|\||```)/', $block)) {
                continue;
            }

            $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $block) ?? $block;
            $text = trim((string) preg_replace('/\s+/', ' ', str_replace(['**', '__', '`', '*'], '', $text)));

            return $text === '' ? null : Str::limit($text, $limit);
        }

        return null;
    }

    /**
     * Load and render a help guide.
     *
     * @return array{key: string, title: string, html: string}|null
     */
    public static function load(string $key): ?array
    {
        $key = self::normalizeKey($key);
        $path = self::path($key);

        if (! is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            return null;
        }

        [$frontMatter, $markdown] = self::splitFrontMatter($raw);
        $title = is_string($frontMatter['title'] ?? null) && $frontMatter['title'] !== ''
            ? $frontMatter['title']
            : Str::headline(str_replace('_', ' ', $key));

        return [
            'key' => $key,
            'title' => $title,
            'html' => Str::markdown($markdown, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ];
    }

    public static function normalizeKey(string $key): string
    {
        return Str::of($key)
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();
    }

    /**
     * @return array{0: array<string, string>, 1: string}
     */
    protected static function splitFrontMatter(string $raw): array
    {
        if (! preg_match('/\A---\r?\n(.*?)\r?\n---\r?\n(.*)\z/s', $raw, $matches)) {
            return [[], ltrim($raw)];
        }

        $frontMatter = [];

        foreach (preg_split('/\r?\n/', $matches[1]) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $frontMatter[trim($name)] = trim($value, " \t\"'");
        }

        return [$frontMatter, ltrim($matches[2])];
    }
}
