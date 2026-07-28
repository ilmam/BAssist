<?php

namespace App\Services;

/**
 * Dry-run result for importing a .feature document (replace or create).
 *
 * @phpstan-type ScenarioBlock array{title: string, body: string, is_outline: bool}
 * @phpstan-type Warning array{code: string, level: string, message: string}
 */
class FeatureImportPreview
{
    public const MODE_REPLACE = 'replace';

    public const MODE_CREATE = 'create';

    /**
     * @param  list<ScenarioBlock>  $incomingScenarios
     * @param  list<string>  $existingScenarioTitles
     * @param  list<string>  $incomingScenarioTitles
     * @param  list<string>  $removedScenarioTitles
     * @param  list<string>  $addedScenarioTitles
     * @param  list<string>  $matchedScenarioTitles
     * @param  list<string>  $incomingFeatureTags
     * @param  list<Warning>  $warnings
     * @param  list<string>  $preservedFields
     */
    public function __construct(
        public readonly string $mode,
        public readonly string $source,
        public readonly string $preamble,
        public readonly array $incomingScenarios,
        public readonly ?string $incomingTitle,
        public readonly ?string $existingTitle,
        public readonly bool $titleMismatch,
        public readonly array $existingScenarioTitles,
        public readonly array $incomingScenarioTitles,
        public readonly array $removedScenarioTitles,
        public readonly array $addedScenarioTitles,
        public readonly array $matchedScenarioTitles,
        public readonly array $incomingFeatureTags,
        public readonly ?string $fileNeedTag,
        public readonly ?string $linkedNeedCode,
        public readonly bool $needTagMismatch,
        public readonly array $warnings,
        public readonly array $preservedFields,
        public readonly ?int $featureId = null,
        public readonly ?int $projectId = null,
        public readonly string $filename = '',
    ) {
    }

    public function scenarioCountExisting(): int
    {
        return count($this->existingScenarioTitles);
    }

    public function scenarioCountIncoming(): int
    {
        return count($this->incomingScenarioTitles);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'source' => $this->source,
            'preamble' => $this->preamble,
            'incoming_scenarios' => $this->incomingScenarios,
            'incoming_title' => $this->incomingTitle,
            'existing_title' => $this->existingTitle,
            'title_mismatch' => $this->titleMismatch,
            'existing_scenario_titles' => $this->existingScenarioTitles,
            'incoming_scenario_titles' => $this->incomingScenarioTitles,
            'removed_scenario_titles' => $this->removedScenarioTitles,
            'added_scenario_titles' => $this->addedScenarioTitles,
            'matched_scenario_titles' => $this->matchedScenarioTitles,
            'incoming_feature_tags' => $this->incomingFeatureTags,
            'file_need_tag' => $this->fileNeedTag,
            'linked_need_code' => $this->linkedNeedCode,
            'need_tag_mismatch' => $this->needTagMismatch,
            'warnings' => $this->warnings,
            'preserved_fields' => $this->preservedFields,
            'feature_id' => $this->featureId,
            'project_id' => $this->projectId,
            'filename' => $this->filename,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mode: (string) ($data['mode'] ?? self::MODE_REPLACE),
            source: (string) ($data['source'] ?? ''),
            preamble: (string) ($data['preamble'] ?? ''),
            incomingScenarios: array_values($data['incoming_scenarios'] ?? []),
            incomingTitle: $data['incoming_title'] ?? null,
            existingTitle: $data['existing_title'] ?? null,
            titleMismatch: (bool) ($data['title_mismatch'] ?? false),
            existingScenarioTitles: array_values($data['existing_scenario_titles'] ?? []),
            incomingScenarioTitles: array_values($data['incoming_scenario_titles'] ?? []),
            removedScenarioTitles: array_values($data['removed_scenario_titles'] ?? []),
            addedScenarioTitles: array_values($data['added_scenario_titles'] ?? []),
            matchedScenarioTitles: array_values($data['matched_scenario_titles'] ?? []),
            incomingFeatureTags: array_values($data['incoming_feature_tags'] ?? []),
            fileNeedTag: $data['file_need_tag'] ?? null,
            linkedNeedCode: $data['linked_need_code'] ?? null,
            needTagMismatch: (bool) ($data['need_tag_mismatch'] ?? false),
            warnings: array_values($data['warnings'] ?? []),
            preservedFields: array_values($data['preserved_fields'] ?? []),
            featureId: isset($data['feature_id']) ? (int) $data['feature_id'] : null,
            projectId: isset($data['project_id']) ? (int) $data['project_id'] : null,
            filename: (string) ($data['filename'] ?? ''),
        );
    }
}
