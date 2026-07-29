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
