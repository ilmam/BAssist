<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Scenario;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Parse .feature documents and apply create/replace imports.
 *
 * Feature-scoped replace preserves non-document fields (code, project, need FK,
 * priority, status). Project-level create can call previewCreate/applyCreate later.
 */
class FeatureImportService
{
    public const SESSION_KEY = 'feature_import_preview';

    public function __construct(
        protected GherkinDocumentParser $parser = new GherkinDocumentParser,
    ) {
    }

    /**
     * @return array{preamble: string, scenarios: list<array{title: string, body: string, is_outline: bool}>, title: ?string}
     */
    public function parse(string $source): array
    {
        $source = str_replace(["\r\n", "\r"], "\n", $source);
        if (trim($source) === '') {
            throw new InvalidArgumentException(__('ui.feature_import_empty_file'));
        }

        $parsed = $this->parser->splitFeatureFile($source);
        $preamble = $parsed['preamble'];
        $title = $this->parser->extractFeatureTitle($preamble);

        return [
            'preamble' => $preamble,
            'scenarios' => $parsed['scenarios'],
            'title' => $title,
        ];
    }

    public function previewReplace(Feature $feature, string $source, string $filename = ''): FeatureImportPreview
    {
        $feature->loadMissing(['scenarios', 'stakeholderNeed']);

        $parsed = $this->parse($source);
        $incomingTitle = $parsed['title'];
        $existingTitle = trim((string) $feature->title);
        $titleMismatch = $incomingTitle !== null
            && $existingTitle !== ''
            && strcasecmp($incomingTitle, $existingTitle) !== 0;

        $existingTitles = $feature->scenarios
            ->map(fn (Scenario $scenario): string => (string) $scenario->title)
            ->filter(fn (string $title): bool => $title !== '')
            ->values()
            ->all();

        $incomingTitles = array_map(
            static fn (array $block): string => (string) $block['title'],
            $parsed['scenarios']
        );

        $existingSet = array_fill_keys(array_map('mb_strtolower', $existingTitles), true);
        $incomingSet = array_fill_keys(array_map('mb_strtolower', $incomingTitles), true);

        $removed = array_values(array_filter(
            $existingTitles,
            static fn (string $title): bool => ! isset($incomingSet[mb_strtolower($title)])
        ));
        $added = array_values(array_filter(
            $incomingTitles,
            static fn (string $title): bool => ! isset($existingSet[mb_strtolower($title)])
        ));
        $matched = array_values(array_filter(
            $incomingTitles,
            static fn (string $title): bool => isset($existingSet[mb_strtolower($title)])
        ));

        $featureTags = $this->parser->leadingTags($parsed['preamble']);
        $fileNeed = $this->extractNeedCodeFromTags($featureTags);
        $linkedNeed = $feature->stakeholderNeed?->code;
        $needMismatch = $fileNeed !== null
            && $linkedNeed !== null
            && strcasecmp($fileNeed, $linkedNeed) !== 0;

        $warnings = $this->buildReplaceWarnings(
            incomingTitle: $incomingTitle,
            titleMismatch: $titleMismatch,
            existingTitle: $existingTitle !== '' ? $existingTitle : null,
            scenarioCountExisting: count($existingTitles),
            scenarioCountIncoming: count($incomingTitles),
            removed: $removed,
            added: $added,
            needMismatch: $needMismatch,
            fileNeed: $fileNeed,
            linkedNeed: $linkedNeed,
            hasLinkedNeed: $feature->stakeholder_need_id !== null,
        );

        return new FeatureImportPreview(
            mode: FeatureImportPreview::MODE_REPLACE,
            source: $source,
            preamble: $parsed['preamble'],
            incomingScenarios: $parsed['scenarios'],
            incomingTitle: $incomingTitle,
            existingTitle: $existingTitle !== '' ? $existingTitle : null,
            titleMismatch: $titleMismatch,
            existingScenarioTitles: $existingTitles,
            incomingScenarioTitles: $incomingTitles,
            removedScenarioTitles: $removed,
            addedScenarioTitles: $added,
            matchedScenarioTitles: $matched,
            incomingFeatureTags: $featureTags,
            fileNeedTag: $fileNeed,
            linkedNeedCode: $linkedNeed,
            needTagMismatch: $needMismatch,
            warnings: $warnings,
            preservedFields: [
                'code',
                'project_id',
                'stakeholder_need_id',
                'priority_id',
                'status_id',
            ],
            featureId: $feature->id ? (int) $feature->id : null,
            projectId: $feature->project_id ? (int) $feature->project_id : null,
            filename: $filename,
        );
    }

    /**
     * Preview creating a Feature from a document (project-level import entry point).
     */
    public function previewCreate(int $projectId, string $source, string $filename = ''): FeatureImportPreview
    {
        $parsed = $this->parse($source);
        $incomingTitle = $parsed['title'];
        $incomingTitles = array_map(
            static fn (array $block): string => (string) $block['title'],
            $parsed['scenarios']
        );
        $featureTags = $this->parser->leadingTags($parsed['preamble']);
        $fileNeed = $this->extractNeedCodeFromTags($featureTags);

        $warnings = [];
        if ($incomingTitle === null) {
            $warnings[] = [
                'code' => 'missing_feature_title',
                'level' => 'warning',
                'message' => __('ui.feature_import_warn_missing_title'),
            ];
        }
        if ($incomingTitles === []) {
            $warnings[] = [
                'code' => 'no_scenarios',
                'level' => 'warning',
                'message' => __('ui.feature_import_warn_no_scenarios'),
            ];
        }
        $warnings[] = [
            'code' => 'blank_metadata',
            'level' => 'info',
            'message' => __('ui.feature_import_warn_blank_metadata'),
        ];
        if ($fileNeed !== null) {
            $warnings[] = [
                'code' => 'need_tag_not_linked',
                'level' => 'info',
                'message' => __('ui.feature_import_warn_need_tag_not_linked', ['code' => $fileNeed]),
            ];
        }

        return new FeatureImportPreview(
            mode: FeatureImportPreview::MODE_CREATE,
            source: $source,
            preamble: $parsed['preamble'],
            incomingScenarios: $parsed['scenarios'],
            incomingTitle: $incomingTitle,
            existingTitle: null,
            titleMismatch: false,
            existingScenarioTitles: [],
            incomingScenarioTitles: $incomingTitles,
            removedScenarioTitles: [],
            addedScenarioTitles: $incomingTitles,
            matchedScenarioTitles: [],
            incomingFeatureTags: $featureTags,
            fileNeedTag: $fileNeed,
            linkedNeedCode: null,
            needTagMismatch: false,
            warnings: $warnings,
            preservedFields: [],
            featureId: null,
            projectId: $projectId,
            filename: $filename,
        );
    }

    /**
     * Replace Feature document + scenarios. Preserves code, project, need, priority, status.
     *
     * @param  array{overwrite_title?: bool}  $options
     */
    public function applyReplace(Feature $feature, string $source, array $options = []): Feature
    {
        $overwriteTitle = (bool) ($options['overwrite_title'] ?? true);
        $parsed = $this->parse($source);

        return DB::transaction(function () use ($feature, $parsed, $overwriteTitle): Feature {
            $feature->body = $parsed['preamble'];
            if ($overwriteTitle && $parsed['title'] !== null) {
                $feature->title = $parsed['title'];
            } elseif (trim((string) $feature->title) === '' && $parsed['title'] !== null) {
                $feature->title = $parsed['title'];
            }

            $feature->syncDocumentFields($this->parser);
            $feature->save();

            $feature->scenarios()->delete();

            foreach ($parsed['scenarios'] as $block) {
                $scenario = new Scenario([
                    'feature_id' => $feature->id,
                    'title' => $block['title'],
                    'body' => $block['body'],
                    'is_outline' => $block['is_outline'],
                ]);
                $scenario->syncDocumentFields($this->parser);
                $scenario->save();
            }

            return $feature->fresh([
                'scenarios' => fn ($query) => $query->orderBy('id'),
                'project',
                'stakeholderNeed',
                'priority',
                'status',
            ]) ?? $feature;
        });
    }

    /**
     * Create a Feature + scenarios from a document. Leaves need/priority blank when not supplied.
     *
     * @param  array{title?: ?string, stakeholder_need_id?: ?int, priority_id?: ?int, status_id?: ?int}  $attributes
     */
    public function applyCreate(int $projectId, string $source, array $attributes = []): Feature
    {
        $parsed = $this->parse($source);
        $title = $attributes['title'] ?? $parsed['title'] ?? 'Untitled';

        return DB::transaction(function () use ($projectId, $parsed, $title, $attributes): Feature {
            $feature = new Feature([
                'title' => $title,
                'project_id' => $projectId,
                'body' => $parsed['preamble'],
                'stakeholder_need_id' => $attributes['stakeholder_need_id'] ?? null,
                'priority_id' => $attributes['priority_id'] ?? null,
                'status_id' => $attributes['status_id'] ?? null,
            ]);
            $feature->syncDocumentFields($this->parser);
            $feature->save();

            foreach ($parsed['scenarios'] as $block) {
                $scenario = new Scenario([
                    'feature_id' => $feature->id,
                    'title' => $block['title'],
                    'body' => $block['body'],
                    'is_outline' => $block['is_outline'],
                ]);
                $scenario->syncDocumentFields($this->parser);
                $scenario->save();
            }

            return $feature->fresh([
                'scenarios' => fn ($query) => $query->orderBy('id'),
                'project',
                'stakeholderNeed',
                'priority',
                'status',
            ]) ?? $feature;
        });
    }

    /**
     * @param  list<string>  $tags
     */
    public function extractNeedCodeFromTags(array $tags): ?string
    {
        foreach ($tags as $tag) {
            if (preg_match('/^@need:(.+)$/i', $tag, $match) === 1) {
                $code = trim($match[1]);

                return $code !== '' ? $code : null;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $removed
     * @param  list<string>  $added
     * @return list<array{code: string, level: string, message: string}>
     */
    protected function buildReplaceWarnings(
        ?string $incomingTitle,
        bool $titleMismatch,
        ?string $existingTitle,
        int $scenarioCountExisting,
        int $scenarioCountIncoming,
        array $removed,
        array $added,
        bool $needMismatch,
        ?string $fileNeed,
        ?string $linkedNeed,
        bool $hasLinkedNeed,
    ): array {
        $warnings = [];

        if ($incomingTitle === null) {
            $warnings[] = [
                'code' => 'missing_feature_title',
                'level' => 'warning',
                'message' => __('ui.feature_import_warn_missing_title'),
            ];
        }

        if ($titleMismatch) {
            $warnings[] = [
                'code' => 'title_mismatch',
                'level' => 'warning',
                'message' => __('ui.feature_import_warn_title_mismatch', [
                    'current' => $existingTitle,
                    'incoming' => $incomingTitle,
                ]),
            ];
        }

        if ($scenarioCountIncoming === 0) {
            $warnings[] = [
                'code' => 'no_scenarios',
                'level' => 'warning',
                'message' => __('ui.feature_import_warn_no_scenarios_replace'),
            ];
        } elseif ($scenarioCountExisting !== $scenarioCountIncoming || $removed !== [] || $added !== []) {
            $warnings[] = [
                'code' => 'scenario_replace',
                'level' => 'warning',
                'message' => __('ui.feature_import_warn_scenario_replace', [
                    'existing' => $scenarioCountExisting,
                    'incoming' => $scenarioCountIncoming,
                ]),
            ];
        }

        if ($removed !== []) {
            $warnings[] = [
                'code' => 'scenarios_removed',
                'level' => 'warning',
                'message' => __('ui.feature_import_warn_scenarios_removed', [
                    'titles' => implode(', ', $removed),
                ]),
            ];
        }

        if ($added !== []) {
            $warnings[] = [
                'code' => 'scenarios_added',
                'level' => 'info',
                'message' => __('ui.feature_import_warn_scenarios_added', [
                    'titles' => implode(', ', $added),
                ]),
            ];
        }

        if ($needMismatch) {
            $warnings[] = [
                'code' => 'need_tag_mismatch',
                'level' => 'warning',
                'message' => __('ui.feature_import_warn_need_mismatch', [
                    'file' => $fileNeed,
                    'linked' => $linkedNeed,
                ]),
            ];
        } elseif ($hasLinkedNeed) {
            $warnings[] = [
                'code' => 'need_preserved',
                'level' => 'info',
                'message' => __('ui.feature_import_warn_need_preserved', [
                    'code' => $linkedNeed,
                ]),
            ];
        }

        $warnings[] = [
            'code' => 'scenario_ids_reset',
            'level' => 'info',
            'message' => __('ui.feature_import_warn_scenario_ids_reset'),
        ];

        return $warnings;
    }
}
