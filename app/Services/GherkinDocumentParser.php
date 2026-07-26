<?php

namespace App\Services;

/**
 * Parse and sync helpers for Feature/Scenario Gherkin document bodies.
 */
class GherkinDocumentParser
{
    /**
     * Split a full .feature file into feature preamble + scenario blocks.
     *
     * @return array{preamble: string, scenarios: list<array{title: string, body: string, is_outline: bool}>}
     */
    public function splitFeatureFile(string $source): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $source);
        $lines = explode("\n", $normalized);
        $preambleLines = [];
        $scenarios = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match('/^\s*(Scenario(?:\s+Outline)?)\s*:\s*(.*)$/i', $line, $match) === 1) {
                if ($current !== null) {
                    $scenarios[] = $this->finalizeScenarioBlock($current);
                }

                $keyword = strcasecmp($match[1], 'Scenario Outline') === 0
                    || preg_match('/outline/i', $match[1]) === 1;
                $current = [
                    'title' => trim($match[2]),
                    'is_outline' => $keyword,
                    'lines' => [$line],
                ];
                continue;
            }

            if ($current === null) {
                $preambleLines[] = $line;
                continue;
            }

            $current['lines'][] = $line;
            if (preg_match('/^\s*Examples\s*:/i', $line) === 1) {
                $current['is_outline'] = true;
            }
        }

        if ($current !== null) {
            $scenarios[] = $this->finalizeScenarioBlock($current);
        }

        return [
            'preamble' => rtrim(implode("\n", $preambleLines)).(trim(implode("\n", $preambleLines)) !== '' ? "\n" : ''),
            'scenarios' => $scenarios,
        ];
    }

    public function extractFeatureTitle(?string $body): ?string
    {
        if ($body === null || trim($body) === '') {
            return null;
        }

        if (preg_match('/^\s*Feature\s*:\s*(.+)$/im', $body, $match) === 1) {
            $title = trim($match[1]);

            return $title !== '' ? mb_substr($title, 0, 255) : null;
        }

        return null;
    }

    public function extractScenarioTitle(?string $body): ?string
    {
        if ($body === null || trim($body) === '') {
            return null;
        }

        if (preg_match('/^\s*Scenario(?:\s+Outline)?\s*:\s*(.+)$/im', $body, $match) === 1) {
            $title = trim($match[1]);

            return $title !== '' ? mb_substr($title, 0, 255) : null;
        }

        return null;
    }

    public function bodyLooksLikeOutline(?string $body): bool
    {
        $text = (string) $body;

        return preg_match('/^\s*Scenario\s+Outline\s*:/im', $text) === 1
            || preg_match('/^\s*Examples\s*:/im', $text) === 1;
    }

    /**
     * @return list<string>
     */
    public function parseTags(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        preg_match_all('/@[^\s@]+/', $text, $matches);

        return array_values(array_unique(array_filter(
            $matches[0] ?? [],
            static fn (string $token): bool => strlen($token) > 1
        )));
    }

    /**
     * Leading @tags only (before first non-tag content line).
     *
     * @return list<string>
     */
    public function leadingTags(?string $body): array
    {
        if ($body === null || trim($body) === '') {
            return [];
        }

        $tags = [];
        foreach (preg_split("/\r\n|\n|\r/", $body) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (! str_starts_with($trimmed, '@')) {
                break;
            }
            $tags = array_merge($tags, $this->parseTags($trimmed));
        }

        return array_values(array_unique($tags));
    }

    /**
     * Ensure a Feature document has a Feature: line (using $fallbackTitle when missing).
     */
    public function ensureFeatureHeader(?string $body, string $fallbackTitle): string
    {
        $text = trim((string) $body);
        if ($text === '') {
            $title = trim($fallbackTitle) !== '' ? trim($fallbackTitle) : 'Untitled';

            return "Feature: {$title}\n";
        }

        if (preg_match('/^\s*Feature\s*:/im', $text) === 1) {
            return rtrim($text)."\n";
        }

        $title = trim($fallbackTitle) !== '' ? trim($fallbackTitle) : 'Untitled';

        return "Feature: {$title}\n\n".rtrim($text)."\n";
    }

    /**
     * Keep a single leading @need:{code} tag in sync with Feature.stakeholder_need_id.
     * Replaces any existing @need:… token; removes it when $needCode is empty.
     */
    public function syncNeedTraceabilityTag(?string $body, ?string $needCode): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", (string) $body);
        $lines = explode("\n", $normalized);
        $cleaned = [];

        foreach ($lines as $line) {
            $hadNeedTag = preg_match('/@[Nn]eed:[^\s@]+/', $line) === 1;
            $stripped = preg_replace('/@[Nn]eed:[^\s@]+/', '', $line) ?? $line;
            $stripped = preg_replace('/[ \t]{2,}/', ' ', $stripped) ?? $stripped;
            $stripped = rtrim($stripped);
            if ($hadNeedTag) {
                $stripped = ltrim($stripped);
            }

            if (trim($stripped) === '') {
                if (trim($line) === '') {
                    $cleaned[] = '';
                }
                continue;
            }

            $cleaned[] = $stripped;
        }

        while ($cleaned !== [] && trim((string) $cleaned[0]) === '') {
            array_shift($cleaned);
        }

        $text = rtrim(implode("\n", $cleaned));
        $code = trim((string) $needCode);

        if ($code === '') {
            return $text === '' ? '' : $text."\n";
        }

        $tag = '@need:'.$code;

        if ($text === '') {
            return $tag."\n";
        }

        return $tag."\n".$text."\n";
    }

    /**
     * Ensure a Scenario document has a Scenario:/Scenario Outline: line.
     */
    public function ensureScenarioHeader(?string $body, string $fallbackTitle, bool $isOutline = false): string
    {
        $text = trim((string) $body);
        $keyword = $isOutline || $this->bodyLooksLikeOutline($text) ? 'Scenario Outline' : 'Scenario';
        $title = trim($fallbackTitle) !== '' ? trim($fallbackTitle) : 'Untitled';

        if ($text === '') {
            return "{$keyword}: {$title}\n";
        }

        if (preg_match('/^\s*Scenario(?:\s+Outline)?\s*:/im', $text) === 1) {
            return rtrim($text)."\n";
        }

        return "{$keyword}: {$title}\n\n".rtrim($text)."\n";
    }

    /**
     * @param  array{title: string, is_outline: bool, lines: list<string>}  $block
     * @return array{title: string, body: string, is_outline: bool}
     */
    protected function finalizeScenarioBlock(array $block): array
    {
        $body = rtrim(implode("\n", $block['lines']))."\n";
        $title = trim((string) $block['title']);
        if ($title === '') {
            $title = $this->extractScenarioTitle($body) ?? 'Untitled';
        }

        return [
            'title' => mb_substr($title, 0, 255),
            'body' => $body,
            'is_outline' => (bool) $block['is_outline'] || $this->bodyLooksLikeOutline($body),
        ];
    }
}
