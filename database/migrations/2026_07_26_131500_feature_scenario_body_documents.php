<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidate Feature/Scenario split Gherkin fields into one editable document body each.
 * Safe for DBs that already ran the earlier split-field schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->migrateFeatures();
        $this->migrateScenarios();
    }

    public function down(): void
    {
        if (Schema::hasTable('features') && Schema::hasColumn('features', 'body') && ! Schema::hasColumn('features', 'as_a')) {
            Schema::table('features', function (Blueprint $table) {
                $table->text('tags')->nullable()->after('trace_notes');
                $table->text('as_a')->nullable()->after('tags');
                $table->text('i_want')->nullable()->after('as_a');
                $table->text('in_order_to')->nullable()->after('i_want');
                $table->text('description')->nullable()->after('in_order_to');
                $table->text('background')->nullable()->after('description');
            });

            Schema::table('features', function (Blueprint $table) {
                $table->dropColumn('body');
            });
        }

        if (Schema::hasTable('scenarios') && Schema::hasColumn('scenarios', 'body') && ! Schema::hasColumn('scenarios', 'steps')) {
            Schema::table('scenarios', function (Blueprint $table) {
                $table->text('tags')->nullable()->after('feature_id');
                $table->text('description')->nullable()->after('tags');
                $table->text('steps')->nullable()->after('is_outline');
            });

            foreach (DB::table('scenarios')->select('id', 'body')->cursor() as $row) {
                DB::table('scenarios')->where('id', $row->id)->update([
                    'steps' => $row->body,
                ]);
            }

            Schema::table('scenarios', function (Blueprint $table) {
                $table->dropColumn('body');
            });
        }
    }

    protected function migrateFeatures(): void
    {
        if (! Schema::hasTable('features')) {
            return;
        }

        if (! Schema::hasColumn('features', 'body')) {
            Schema::table('features', function (Blueprint $table) {
                $table->longText('body')->nullable()->after('trace_notes');
            });
        }

        $hasLegacy = Schema::hasColumn('features', 'as_a')
            || Schema::hasColumn('features', 'tags')
            || Schema::hasColumn('features', 'background');

        if ($hasLegacy) {
            $columns = ['id', 'title', 'body'];
            foreach (['tags', 'as_a', 'i_want', 'in_order_to', 'description', 'background'] as $col) {
                if (Schema::hasColumn('features', $col)) {
                    $columns[] = $col;
                }
            }

            foreach (DB::table('features')->select($columns)->cursor() as $row) {
                $existingBody = trim((string) ($row->body ?? ''));
                if ($existingBody !== '') {
                    continue;
                }

                DB::table('features')->where('id', $row->id)->update([
                    'body' => $this->buildFeatureBody($row),
                ]);
            }

            $drop = array_values(array_filter(
                ['tags', 'as_a', 'i_want', 'in_order_to', 'description', 'background'],
                static fn (string $col): bool => Schema::hasColumn('features', $col)
            ));

            if ($drop !== []) {
                Schema::table('features', function (Blueprint $table) use ($drop) {
                    $table->dropColumn($drop);
                });
            }
        }
    }

    protected function migrateScenarios(): void
    {
        if (! Schema::hasTable('scenarios')) {
            return;
        }

        if (Schema::hasColumn('scenarios', 'steps') && ! Schema::hasColumn('scenarios', 'body')) {
            Schema::table('scenarios', function (Blueprint $table) {
                $table->longText('body')->nullable()->after('is_outline');
            });

            $columns = ['id', 'title', 'is_outline', 'steps'];
            foreach (['tags', 'description'] as $col) {
                if (Schema::hasColumn('scenarios', $col)) {
                    $columns[] = $col;
                }
            }

            foreach (DB::table('scenarios')->select($columns)->cursor() as $row) {
                DB::table('scenarios')->where('id', $row->id)->update([
                    'body' => $this->buildScenarioBody($row),
                ]);
            }

            $drop = array_values(array_filter(
                ['tags', 'description', 'steps'],
                static fn (string $col): bool => Schema::hasColumn('scenarios', $col)
            ));

            if ($drop !== []) {
                Schema::table('scenarios', function (Blueprint $table) use ($drop) {
                    $table->dropColumn($drop);
                });
            }

            return;
        }

        if (! Schema::hasColumn('scenarios', 'body')) {
            Schema::table('scenarios', function (Blueprint $table) {
                $table->longText('body')->nullable()->after('is_outline');
            });
        }

        if (Schema::hasColumn('scenarios', 'tags') || Schema::hasColumn('scenarios', 'description')) {
            $columns = ['id', 'title', 'is_outline', 'body'];
            foreach (['tags', 'description'] as $col) {
                if (Schema::hasColumn('scenarios', $col)) {
                    $columns[] = $col;
                }
            }

            foreach (DB::table('scenarios')->select($columns)->cursor() as $row) {
                $body = trim((string) ($row->body ?? ''));
                $tags = trim((string) ($row->tags ?? ''));
                $description = trim((string) ($row->description ?? ''));
                if ($tags === '' && $description === '') {
                    continue;
                }

                DB::table('scenarios')->where('id', $row->id)->update([
                    'body' => $this->buildScenarioBody((object) array_merge(
                        (array) $row,
                        ['steps' => $body]
                    )),
                ]);
            }

            $drop = array_values(array_filter(
                ['tags', 'description'],
                static fn (string $col): bool => Schema::hasColumn('scenarios', $col)
            ));

            if ($drop !== []) {
                Schema::table('scenarios', function (Blueprint $table) use ($drop) {
                    $table->dropColumn($drop);
                });
            }
        }
    }

    protected function buildFeatureBody(object $row): string
    {
        $lines = [];

        $tags = $this->parseTags((string) ($row->tags ?? ''));
        if ($tags !== []) {
            $lines[] = implode(' ', $tags);
        }

        $title = trim((string) ($row->title ?? ''));
        $lines[] = 'Feature: '.($title !== '' ? $title : 'Untitled');

        $asA = trim((string) ($row->as_a ?? ''));
        if ($asA !== '') {
            $lines[] = '  As a '.$this->stripLeadingPhrase($asA, ['as a', 'as an']);
        }

        $iWant = trim((string) ($row->i_want ?? ''));
        if ($iWant !== '') {
            $lines[] = '  I want '.$this->stripLeadingPhrase($iWant, ['i want', 'i want to']);
        }

        $inOrderTo = trim((string) ($row->in_order_to ?? ''));
        if ($inOrderTo !== '') {
            $lines[] = '  In order to '.$this->stripLeadingPhrase($inOrderTo, ['in order to']);
        }

        $description = trim((string) ($row->description ?? ''));
        if ($description !== '') {
            foreach (preg_split("/\r\n|\n|\r/", $description) ?: [] as $line) {
                $lines[] = '  '.$line;
            }
        }

        $background = trim((string) ($row->background ?? ''));
        if ($background !== '') {
            $lines[] = '';
            if (preg_match('/^\s*Background\s*:/i', $background) === 1) {
                foreach (preg_split("/\r\n|\n|\r/", $background) ?: [] as $line) {
                    $lines[] = $line === '' ? '' : '  '.ltrim($line);
                }
            } else {
                $lines[] = '  Background:';
                foreach (preg_split("/\r\n|\n|\r/", $background) ?: [] as $line) {
                    $lines[] = $line === '' ? '' : '    '.ltrim($line);
                }
            }
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    protected function buildScenarioBody(object $row): string
    {
        $steps = trim((string) ($row->steps ?? $row->body ?? ''));
        $tags = $this->parseTags((string) ($row->tags ?? ''));
        $description = trim((string) ($row->description ?? ''));
        $title = trim((string) ($row->title ?? ''));
        $isOutline = (bool) ($row->is_outline ?? false)
            || preg_match('/^\s*Examples\s*:/im', $steps) === 1
            || preg_match('/^\s*Scenario\s+Outline\s*:/im', $steps) === 1;

        // Already a full scenario block — prepend missing tags only.
        if (preg_match('/^\s*(@\S+.*\n\s*)*(Scenario(?:\s+Outline)?)\s*:/im', $steps) === 1) {
            if ($tags === []) {
                return rtrim($steps)."\n";
            }

            $tagLine = implode(' ', $tags);
            if (str_contains($steps, $tagLine)) {
                return rtrim($steps)."\n";
            }

            return rtrim($tagLine."\n".$steps)."\n";
        }

        $lines = [];
        if ($tags !== []) {
            $lines[] = implode(' ', $tags);
        }

        $keyword = $isOutline ? 'Scenario Outline' : 'Scenario';
        $lines[] = $keyword.': '.($title !== '' ? $title : 'Untitled');

        if ($description !== '') {
            foreach (preg_split("/\r\n|\n|\r/", $description) ?: [] as $line) {
                $lines[] = '  '.$line;
            }
        }

        if ($steps !== '') {
            foreach (preg_split("/\r\n|\n|\r/", $steps) ?: [] as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    $lines[] = '';
                    continue;
                }
                // Keep leading @tag lines unindented; indent step content lightly if bare.
                if (str_starts_with($trimmed, '@')) {
                    $lines[] = $trimmed;
                } else {
                    $lines[] = preg_match('/^\s+/', $line) === 1 ? rtrim($line) : '  '.$trimmed;
                }
            }
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    /**
     * @return list<string>
     */
    protected function parseTags(string $tags): array
    {
        if (trim($tags) === '') {
            return [];
        }

        preg_match_all('/@[^\s@]+/', $tags, $matches);

        return array_values(array_unique(array_filter(
            $matches[0] ?? [],
            static fn (string $token): bool => strlen($token) > 1
        )));
    }

    /**
     * @param  list<string>  $phrases
     */
    protected function stripLeadingPhrase(string $value, array $phrases): string
    {
        foreach ($phrases as $phrase) {
            $pattern = '/^'.preg_quote($phrase, '/').'\s+/i';
            if (preg_match($pattern, $value) === 1) {
                return trim((string) preg_replace($pattern, '', $value, 1));
            }
        }

        return $value;
    }
};
