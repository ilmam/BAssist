<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Scenario;
use App\Models\Workspace;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds a derived acceptance-plan checklist from BDD scenarios and FR acceptance criteria.
 */
class AcceptancePlanBuilder
{
    public const TYPE_EDGE_CASE = 'Edge Case';

    public const TYPE_HAPPY_PATH = 'Happy Path';

    public const STATUS_DEFAULT = 'Draft';

    public const SOURCE_BDD = 'bdd';

    public const SOURCE_FR = 'fr';

    public function __construct(
        protected WorkspaceContext $workspaceContext,
        protected ProjectContext $projectContext,
        protected GherkinDocumentParser $parser = new GherkinDocumentParser,
    ) {
    }

    /**
     * @param  array{
     *   project_id?: int|string|null,
     *   feature_id?: int|string|null,
     *   type?: string|null,
     *   stakeholder_need_id?: int|string|null
     * }  $filters
     * @return array{
     *   rows: list<array<string, mixed>>,
     *   summary: array{total: int, edge_case: int, happy_path: int, bdd: int, fr: int},
     *   projects: Collection<int, Project>,
     *   features: Collection<int, Feature>,
     *   filters: array{
     *     project_id: int|null,
     *     feature_id: int|null,
     *     type: string|null,
     *     stakeholder_need_id: int|null,
     *     workspace_id: int|null,
     *     workspace_name: string|null
     *   }
     * }
     */
    public function build(array $filters = []): array
    {
        $projectId = isset($filters['project_id']) && is_numeric($filters['project_id'])
            ? (int) $filters['project_id']
            : $this->projectContext->id();
        $featureId = isset($filters['feature_id']) && is_numeric($filters['feature_id'])
            ? (int) $filters['feature_id']
            : null;
        $stakeholderNeedId = isset($filters['stakeholder_need_id']) && is_numeric($filters['stakeholder_need_id'])
            ? (int) $filters['stakeholder_need_id']
            : null;
        $typeFilter = $this->normalizeTypeFilter($filters['type'] ?? null);

        $workspaceId = $this->workspaceContext->id();
        $workspaceName = $workspaceId
            ? Workspace::query()->whereKey($workspaceId)->value('name')
            : null;

        $features = $this->scopedFeatureQuery($projectId, $workspaceId)
            ->with([
                'status:id,name,code',
                'stakeholderNeed:id,number,title',
                'scenarios' => fn ($query) => $query->orderBy('id')->with('status:id,name,code'),
            ])
            ->when($featureId !== null, fn (Builder $query) => $query->whereKey($featureId))
            ->when($stakeholderNeedId !== null, fn (Builder $query) => $query->where('stakeholder_need_id', $stakeholderNeedId))
            ->orderBy('number')
            ->orderBy('title')
            ->get();

        $rows = collect($this->rowsForFeatures($features));

        // Feature filter scopes to BDD only; otherwise merge FR acceptance checks.
        if ($featureId === null) {
            $requirements = $this->scopedFunctionalRequirementQuery($projectId, $workspaceId)
                ->with([
                    'status:id,name,code',
                    'stakeholderNeed:id,number,title',
                ])
                ->when($stakeholderNeedId !== null, fn (Builder $query) => $query->where('stakeholder_need_id', $stakeholderNeedId))
                ->orderBy('number')
                ->orderBy('title')
                ->get();

            $rows = $rows->merge($this->rowsForFunctionalRequirements($requirements));
        }

        if ($typeFilter !== null) {
            $rows = $rows->filter(fn (array $row) => $row['type'] === $typeFilter)->values();
        }

        $rows = $rows
            ->sortBy([
                ['source', 'asc'],
                ['feature_code', 'asc'],
                ['test_id', 'asc'],
            ])
            ->values();

        return [
            'rows' => $rows->all(),
            'summary' => [
                'total' => $rows->count(),
                'edge_case' => $rows->where('type', self::TYPE_EDGE_CASE)->count(),
                'happy_path' => $rows->where('type', self::TYPE_HAPPY_PATH)->count(),
                'bdd' => $rows->where('source', self::SOURCE_BDD)->count(),
                'fr' => $rows->where('source', self::SOURCE_FR)->count(),
            ],
            'projects' => $this->projectsForFilter($workspaceId),
            'features' => $this->featuresForFilter($projectId, $workspaceId),
            'filters' => [
                'project_id' => $projectId,
                'feature_id' => $featureId,
                'type' => $typeFilter,
                'stakeholder_need_id' => $stakeholderNeedId,
                'workspace_id' => $workspaceId,
                'workspace_name' => $workspaceName,
            ],
        ];
    }

    /**
     * @param  iterable<int, Feature>  $features
     * @return list<array<string, mixed>>
     */
    public function rowsForFeatures(iterable $features): array
    {
        $rows = [];

        foreach ($features as $feature) {
            $prefix = $this->testIdPrefix($feature->code, (string) ($feature->title ?? ''));
            $sequence = 0;
            $scenarios = $feature->relationLoaded('scenarios')
                ? $feature->scenarios
                : $feature->scenarios()->orderBy('id')->with('status:id,name,code')->get();

            foreach ($scenarios as $scenario) {
                $sequence++;
                $rows[] = $this->rowFromScenario($feature, $scenario, $prefix, $sequence);
            }
        }

        return $rows;
    }

    /**
     * @param  iterable<int, FunctionalRequirement>  $requirements
     * @return list<array<string, mixed>>
     */
    public function rowsForFunctionalRequirements(iterable $requirements): array
    {
        $rows = [];

        foreach ($requirements as $requirement) {
            $checks = $this->acceptanceCriteriaLines($requirement->acceptance_criteria);
            if ($checks === []) {
                continue;
            }

            $prefix = $this->testIdPrefix($requirement->code, (string) ($requirement->title ?? ''));
            $sequence = 0;

            foreach ($checks as $check) {
                $sequence++;
                $rows[] = $this->rowFromFunctionalRequirement($requirement, $check, $prefix, $sequence);
            }
        }

        return $rows;
    }

    /**
     * Split acceptance criteria into checklist lines (bullets / numbered / plain lines).
     *
     * @return list<string>
     */
    public function acceptanceCriteriaLines(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $lines = preg_split("/\r\n|\n|\r/", $text) ?: [];
        $checks = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $line = preg_replace('/^(?:[-*•]|\d+[.)])\s+/u', '', $line) ?? $line;
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $checks[] = $line;
        }

        return $checks;
    }

    /**
     * Prefer Feature/FR code (FE-n / FR-n); otherwise initials from the title.
     */
    public function testIdPrefix(?string $featureCode, string $featureTitle): string
    {
        $code = trim((string) $featureCode);
        if ($code !== '') {
            return $code;
        }

        $initials = $this->initialsFromTitle($featureTitle);

        return $initials !== '' ? $initials : 'FE';
    }

    public function formatTestId(string $prefix, int $sequence): string
    {
        return $prefix.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Edge Case if @edge-case appears in scenario/feature tags or body; else Happy Path.
     */
    public function resolveType(?string $featureBody, ?string $scenarioBody): string
    {
        $haystack = strtolower(trim((string) $featureBody)."\n".trim((string) $scenarioBody));

        if ($haystack !== "\n" && str_contains($haystack, '@edge-case')) {
            return self::TYPE_EDGE_CASE;
        }

        return self::TYPE_HAPPY_PATH;
    }

    public function resolveAcceptanceCheckType(string $check): string
    {
        $normalized = strtolower($check);

        if (str_contains($normalized, '@edge-case') || str_starts_with($normalized, 'edge:')) {
            return self::TYPE_EDGE_CASE;
        }

        return self::TYPE_HAPPY_PATH;
    }

    public function resolveStatus(?Scenario $scenario, ?Feature $feature): string
    {
        $name = $scenario?->status?->name
            ?? $feature?->status?->name
            ?? null;

        $name = is_string($name) ? trim($name) : '';

        return $name !== '' ? $name : self::STATUS_DEFAULT;
    }

    /**
     * Explicit Rule: line from feature body; empty when absent.
     */
    public function resolveRule(?string $featureBody): string
    {
        if ($featureBody === null || trim($featureBody) === '') {
            return '';
        }

        if (preg_match('/^\s*Rule\s*:\s*(.+)$/im', $featureBody, $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * BDD Rule / statement: feature-level only (Rule: or As a/I want/So that).
     * Scenario steps stay under the check title — they are not a parent "statement".
     */
    public function resolveBddRule(?string $featureBody): string
    {
        $rule = $this->resolveRule($featureBody);
        if ($rule !== '') {
            return $rule;
        }

        return $this->resolveFeatureStory($featureBody);
    }

    /**
     * Feature user-story framing (As a / I want / So that|In order to).
     */
    public function resolveFeatureStory(?string $featureBody): string
    {
        if ($featureBody === null || trim($featureBody) === '') {
            return '';
        }

        $lines = preg_split("/\r\n|\n|\r/", $featureBody) ?: [];
        $parts = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '@')) {
                continue;
            }

            if (preg_match('/^(As an?|I want|So that|In order to)\b(.*)$/i', $trimmed, $match) !== 1) {
                if (preg_match('/^(Background|Rule|Scenario)\b/i', $trimmed) === 1 && $parts !== []) {
                    break;
                }
                continue;
            }

            $keyword = $match[1];
            $rest = trim($match[2]);
            $parts[] = $rest !== '' ? $keyword.' '.$rest : $keyword;
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowFromScenario(Feature $feature, Scenario $scenario, string $prefix, int $sequence): array
    {
        return [
            'source' => self::SOURCE_BDD,
            'test_id' => $this->formatTestId($prefix, $sequence),
            'feature_id' => $feature->id,
            'feature_title' => $feature->title,
            'feature_code' => $feature->code,
            'functional_requirement_id' => null,
            'scenario_id' => $scenario->id,
            'scenario_title' => $scenario->title,
            'rule' => $this->resolveBddRule($feature->body),
            'type' => $this->resolveType($feature->body, $scenario->body),
            'status' => $this->resolveStatus($scenario, $feature),
            'need_id' => $feature->stakeholder_need_id,
            'need_code' => $feature->stakeholderNeed?->code,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowFromFunctionalRequirement(
        FunctionalRequirement $requirement,
        string $check,
        string $prefix,
        int $sequence,
    ): array {
        $statusName = is_string($requirement->status?->name)
            ? trim($requirement->status->name)
            : '';

        return [
            'source' => self::SOURCE_FR,
            'test_id' => $this->formatTestId($prefix, $sequence),
            'feature_id' => null,
            'feature_title' => $requirement->title,
            'feature_code' => $requirement->code,
            'functional_requirement_id' => $requirement->id,
            'scenario_id' => null,
            'scenario_title' => $check,
            'rule' => trim((string) $requirement->statement),
            'type' => $this->resolveAcceptanceCheckType($check),
            'status' => $statusName !== '' ? $statusName : self::STATUS_DEFAULT,
            'need_id' => $requirement->stakeholder_need_id,
            'need_code' => $requirement->stakeholderNeed?->code,
        ];
    }

    protected function initialsFromTitle(string $title): string
    {
        $words = preg_split('/\s+/', trim($title)) ?: [];
        $letters = [];

        foreach ($words as $word) {
            $word = preg_replace('/[^A-Za-z0-9]/', '', $word) ?? '';
            if ($word === '') {
                continue;
            }
            $letters[] = strtoupper($word[0]);
        }

        return implode('', $letters);
    }

    protected function normalizeTypeFilter(mixed $type): ?string
    {
        if (! is_string($type) || trim($type) === '') {
            return null;
        }

        $normalized = strtolower(trim($type));

        return match ($normalized) {
            'edge case', 'edge_case', 'edge-case', self::TYPE_EDGE_CASE => self::TYPE_EDGE_CASE,
            'happy path', 'happy_path', 'happy-path', self::TYPE_HAPPY_PATH => self::TYPE_HAPPY_PATH,
            default => null,
        };
    }

    /**
     * @return Builder<Feature>
     */
    protected function scopedFeatureQuery(?int $projectId, ?int $workspaceId): Builder
    {
        return $this->applyProjectScope(Feature::query(), $projectId, $workspaceId);
    }

    /**
     * @return Builder<FunctionalRequirement>
     */
    protected function scopedFunctionalRequirementQuery(?int $projectId, ?int $workspaceId): Builder
    {
        return $this->applyProjectScope(FunctionalRequirement::query(), $projectId, $workspaceId);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyProjectScope(Builder $query, ?int $projectId, ?int $workspaceId): Builder
    {
        $tenantId = auth()->user()?->tenant_id;

        if ($tenantId !== null) {
            $query->whereHas('project.workspace', function (Builder $workspaceQuery) use ($tenantId): void {
                $workspaceQuery->where('tenant_id', $tenantId);
            });
        }

        if ($workspaceId !== null) {
            $query->whereHas('project', function (Builder $projectQuery) use ($workspaceId): void {
                $projectQuery->where('workspace_id', $workspaceId);
            });
        }

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        return $query;
    }

    /**
     * @return Collection<int, Project>
     */
    protected function projectsForFilter(?int $workspaceId): Collection
    {
        $query = Project::query()->orderBy('name');
        $tenantId = auth()->user()?->tenant_id;

        if ($tenantId !== null) {
            $query->whereHas('workspace', function (Builder $workspaceQuery) use ($tenantId): void {
                $workspaceQuery->where('tenant_id', $tenantId);
            });
        }

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get(['id', 'name', 'code', 'workspace_id']);
    }

    /**
     * @return Collection<int, Feature>
     */
    protected function featuresForFilter(?int $projectId, ?int $workspaceId): Collection
    {
        if ($projectId === null) {
            return collect();
        }

        return $this->scopedFeatureQuery($projectId, $workspaceId)
            ->orderBy('number')
            ->orderBy('title')
            ->get(['id', 'number', 'title', 'project_id']);
    }
}
