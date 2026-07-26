<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Project;
use App\Models\Scenario;
use App\Models\Workspace;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds a derived acceptance-plan checklist: one row per Scenario.
 */
class AcceptancePlanBuilder
{
    public const TYPE_EDGE_CASE = 'Edge Case';

    public const TYPE_HAPPY_PATH = 'Happy Path';

    public const STATUS_DEFAULT = 'Draft';

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
     *   summary: array{total: int, edge_case: int, happy_path: int},
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

        if ($typeFilter !== null) {
            $rows = $rows->filter(fn (array $row) => $row['type'] === $typeFilter)->values();
        }

        return [
            'rows' => $rows->all(),
            'summary' => [
                'total' => $rows->count(),
                'edge_case' => $rows->where('type', self::TYPE_EDGE_CASE)->count(),
                'happy_path' => $rows->where('type', self::TYPE_HAPPY_PATH)->count(),
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
     * Prefer Feature code (FE-n); otherwise initials from the title.
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

    public function resolveStatus(?Scenario $scenario, ?Feature $feature): string
    {
        $name = $scenario?->status?->name
            ?? $feature?->status?->name
            ?? null;

        $name = is_string($name) ? trim($name) : '';

        return $name !== '' ? $name : self::STATUS_DEFAULT;
    }

    /**
     * Optional Rule: line from feature body; empty when absent.
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
     * @return array<string, mixed>
     */
    protected function rowFromScenario(Feature $feature, Scenario $scenario, string $prefix, int $sequence): array
    {
        return [
            'test_id' => $this->formatTestId($prefix, $sequence),
            'feature_id' => $feature->id,
            'feature_title' => $feature->title,
            'feature_code' => $feature->code,
            'scenario_id' => $scenario->id,
            'scenario_title' => $scenario->title,
            'rule' => $this->resolveRule($feature->body),
            'type' => $this->resolveType($feature->body, $scenario->body),
            'status' => $this->resolveStatus($scenario, $feature),
            'need_id' => $feature->stakeholder_need_id,
            'need_code' => $feature->stakeholderNeed?->code,
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
        $query = Feature::query();
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
