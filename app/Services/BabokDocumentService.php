<?php

namespace App\Services;

use App\Models\Project;
use InvalidArgumentException;

/**
 * Builds one BABOK-aligned printable package per redesign output.
 *
 * The fuller project export pack remains available via ProjectExportService;
 * package documents apply orphan filters on the sections that need them.
 */
class BabokDocumentService
{
    public function __construct(
        protected ProjectExportService $export,
        protected AcceptancePlanBuilder $acceptancePlan,
    ) {
    }

    /**
     * @return list<array{
     *   key: string,
     *   title: string,
     *   babok: string,
     *   purpose: string,
     *   url: string,
     *   section_count: int,
     *   item_count: int,
     *   sections: list<array{heading: string, babok: string}>
     * }>
     */
    public function catalog(Project $project): array
    {
        $pack = $this->basePack($project);
        $catalog = [];

        foreach (array_keys(config('babok_documents.documents', [])) as $key) {
            $meta = $this->documentMeta($key);
            $payload = $this->payloadFor($key, $pack, $project);

            $catalog[] = [
                'key' => $key,
                'title' => __($meta['title']),
                'babok' => $meta['babok'],
                'purpose' => __($meta['purpose']),
                'url' => route('projects.babok.show', [$project, $key]),
                'section_count' => count($meta['sections']),
                'item_count' => $payload['item_count'],
                'sections' => collect($meta['sections'])
                    ->map(fn (array $section) => [
                        'heading' => __($section['heading']),
                        'babok' => $section['babok'],
                    ])
                    ->all(),
            ];
        }

        return $catalog;
    }

    /**
     * @return array{
     *   project: Project,
     *   generated_at: \Illuminate\Support\Carbon,
     *   document: array{
     *     key: string,
     *     title: string,
     *     babok: string,
     *     purpose: string,
     *     sections: list<array{key: string, heading: string, babok: string, partial: string}>
     *   },
     *   pack: array<string, mixed>,
     *   item_count: int,
     *   omitted_orphans: int,
     *   change_requests: \Illuminate\Database\Eloquent\Collection
     * }
     */
    public function build(Project $project, string $key): array
    {
        $meta = $this->documentMeta($key);
        $pack = $this->basePack($project);
        $payload = $this->payloadFor($key, $pack, $project);

        return [
            'project' => $project,
            'generated_at' => $pack['generated_at'],
            'document' => [
                'key' => $key,
                'title' => __($meta['title']),
                'babok' => $meta['babok'],
                'purpose' => __($meta['purpose']),
                'sections' => collect($meta['sections'])
                    ->map(fn (array $section) => [
                        'key' => $section['key'],
                        'heading' => __($section['heading']),
                        'babok' => $section['babok'],
                        'partial' => $section['partial'],
                    ])
                    ->all(),
            ],
            'pack' => $payload['pack'],
            'item_count' => $payload['item_count'],
            'omitted_orphans' => $payload['omitted_orphans'],
            'change_requests' => $project->changeRequests->sortBy('number')->values(),
        ];
    }

    /**
     * @return array{
     *   title: string,
     *   babok: string,
     *   purpose: string,
     *   sections: list<array{key: string, heading: string, babok: string, partial: string, filter_orphans: bool}>
     * }
     */
    public function documentMeta(string $key): array
    {
        $docs = config('babok_documents.documents', []);
        if (! isset($docs[$key])) {
            throw new InvalidArgumentException("Unknown BABOK document [{$key}].");
        }

        return $docs[$key];
    }

    public function hasDocument(string $key): bool
    {
        return isset(config('babok_documents.documents')[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function basePack(Project $project): array
    {
        $project->loadMissing([
            'businessObjectives.businessNeeds',
            'changeRequests.priority',
        ]);

        return $this->export->build($project);
    }

    /**
     * @param  array<string, mixed>  $pack
     * @return array{pack: array<string, mixed>, item_count: int, omitted_orphans: int}
     */
    protected function payloadFor(string $key, array $pack, Project $project): array
    {
        $meta = $this->documentMeta($key);
        $filtered = $pack;
        $omitted = 0;

        foreach ($meta['sections'] as $section) {
            if (! ($section['filter_orphans'] ?? false)) {
                continue;
            }

            [$filtered, $sectionOmitted] = $this->applyOrphanFilters($section['key'], $filtered);
            $omitted += $sectionOmitted;
        }

        $itemCount = 0;
        foreach ($meta['sections'] as $section) {
            $itemCount += $this->sectionItemCount($section['key'], $filtered, $project);
        }

        return [
            'pack' => $filtered,
            'item_count' => $itemCount,
            'omitted_orphans' => $omitted,
        ];
    }

    /**
     * @param  array<string, mixed>  $pack
     */
    protected function sectionItemCount(string $sectionKey, array $pack, Project $project): int
    {
        return match ($sectionKey) {
            'current-state-and-needs' => ($pack['strategic_baseline']?->current_state ? 1 : 0)
                + $pack['needs']->count(),
            'future-state-and-objectives' => ($pack['strategic_baseline']?->future_state ? 1 : 0)
                + $pack['objectives']->count(),
            'risk-assessment' => $pack['risks']->count(),
            'change-strategy-scope' => ($pack['strategic_baseline']?->change_strategy ? 1 : 0)
                + $pack['scope_items']->count()
                + $pack['assumptions']->count(),
            'stakeholder-engagement' => $pack['stakeholders']->count(),
            'stakeholder-requirements' => $pack['stakeholder_needs']->count(),
            'solution-requirements' => $pack['functional_requirements']->count()
                + ($pack['non_functional_requirements']?->count() ?? 0)
                + $pack['constraints']->count()
                + $pack['business_rules']->count(),
            'process-state-models' => count($pack['state_flows'])
                + count($pack['swimlane_flows'])
                + count($pack['architecture']['views'] ?? []),
            'acceptance-criteria' => count($pack['acceptance_rows'] ?? []),
            'traceability-matrix' => count($pack['matrix']['rows'] ?? []),
            'governance' => count($pack['readiness']['items'] ?? [])
                + $project->changeRequests->count(),
            default => 0,
        };
    }

    /**
     * @param  array<string, mixed>  $pack
     * @return array{0: array<string, mixed>, 1: int}
     */
    protected function applyOrphanFilters(string $sectionKey, array $pack): array
    {
        $omitted = 0;
        $filtered = $pack;

        if ($sectionKey === 'future-state-and-objectives') {
            // SMART objectives must derive from needs — drop unlinked placeholders.
            $objectivesBefore = $pack['objectives']->count();
            $filtered['objectives'] = $pack['objectives']
                ->filter(fn ($objective) => $objective->businessNeeds->isNotEmpty())
                ->values();
            $omitted += $objectivesBefore - $filtered['objectives']->count();
        }

        if ($sectionKey === 'stakeholder-requirements') {
            $before = $pack['stakeholder_needs']->count();
            $filtered['stakeholder_needs'] = $pack['stakeholder_needs']
                ->filter(fn ($sn) => $sn->businessObjectives->isNotEmpty())
                ->values();
            $omitted += $before - $filtered['stakeholder_needs']->count();
        }

        if ($sectionKey === 'solution-requirements') {
            $before = $pack['functional_requirements']->count();
            $filtered['functional_requirements'] = $pack['functional_requirements']
                ->filter(fn ($fr) => $fr->stakeholderNeed !== null)
                ->values();
            $omitted += $before - $filtered['functional_requirements']->count();

            $nfrs = $pack['non_functional_requirements'] ?? collect();
            $nfrBefore = $nfrs->count();
            $filtered['non_functional_requirements'] = $nfrs
                ->filter(fn ($nfr) => $nfr->stakeholderNeed !== null)
                ->values();
            $omitted += $nfrBefore - $filtered['non_functional_requirements']->count();
        }

        if ($sectionKey === 'acceptance-criteria') {
            $featuresBefore = count($pack['features']);
            $features = collect($pack['features'])
                ->filter(function (array $item): bool {
                    $feature = $item['model'];
                    $hasParent = $feature->stakeholderNeed !== null;
                    $hasScenarios = $feature->scenarios->isNotEmpty();
                    $hasGherkin = trim((string) ($item['gherkin'] ?? '')) !== '';

                    return $hasParent && ($hasScenarios || $hasGherkin);
                })
                ->values();
            $filtered['features'] = $features->all();
            $omitted += $featuresBefore - $features->count();

            $requirements = $pack['functional_requirements']
                ->filter(function ($fr): bool {
                    if ($fr->stakeholderNeed === null) {
                        return false;
                    }

                    return $this->acceptancePlan->acceptanceCriteriaLines($fr->acceptance_criteria) !== [];
                })
                ->values();

            $frBefore = $pack['functional_requirements']->count();
            $omitted += $frBefore - $requirements->count();

            $nfrs = collect($pack['non_functional_requirements'] ?? [])
                ->filter(function ($nfr): bool {
                    if ($nfr->stakeholderNeed === null) {
                        return false;
                    }

                    return $this->acceptancePlan->acceptanceCriteriaLines($nfr->acceptance_criteria) !== [];
                })
                ->values();

            $nfrBefore = collect($pack['non_functional_requirements'] ?? [])->count();
            $omitted += $nfrBefore - $nfrs->count();

            $featureModels = $features->map(fn (array $item) => $item['model'])->all();
            $rows = collect($this->acceptancePlan->rowsForFeatures($featureModels))
                ->merge($this->acceptancePlan->rowsForFunctionalRequirements($requirements))
                ->merge($this->acceptancePlan->rowsForNonFunctionalRequirements($nfrs))
                ->sortBy([
                    ['source', 'asc'],
                    ['feature_code', 'asc'],
                    ['test_id', 'asc'],
                ])
                ->values()
                ->all();

            $filtered['acceptance_rows'] = $rows;
        }

        return [$filtered, $omitted];
    }
}
