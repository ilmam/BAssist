<?php

namespace App\Services;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\ChangeRequest;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Models\Scenario;
use App\Models\StakeholderNeed;
use App\Support\ChangeRequestStatus;
use App\Support\CrudEntityRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Parent / children / local gaps for Need Spine detail pages.
 *
 * @phpstan-type CascadeLink array{label: string, url: string, modal_url: string, model?: string, code?: string|null, title?: string|null, kind?: string, meta?: string}
     * @phpstan-type CascadeGap array{key: string, label: string, action_label: string|null, action_url: string|null, action_model: string|null, action_ability: string, links?: list<CascadeLink>}
 * @phpstan-type CascadeAddAction array{label: string, url: string, model: string}
 * @phpstan-type CascadeGroup array{key: string, heading: string, empty: string, add_label: string, add_url: string, add_model: string, items: list<CascadeLink>, add_actions?: list<CascadeAddAction>}
 */
class SpineCascadeService
{
    /**
     * @return array{
     *     current_label: string,
     *     parents: list<CascadeLink>,
     *     gaps: list<CascadeGap>,
     *     groups: list<CascadeGroup>
     * }|null
     */
    public function for(string $modelName, int $id): ?array
    {
        $with = $this->eagerLoad($modelName);
        if ($with === null) {
            return null;
        }

        $record = CrudEntityRegistry::repository($modelName)->findModel($id, $with);

        $cascade = match ($modelName) {
            'BusinessNeed' => $this->forNeed($record),
            'BusinessObjective' => $this->forObjective($record),
            'StakeholderNeed' => $this->forStakeholderNeed($record),
            'Feature' => $this->forFeature($record),
            'FunctionalRequirement' => $this->forFunctionalRequirement($record),
            'NonFunctionalRequirement' => $this->forNonFunctionalRequirement($record),
            'Scenario' => $this->forScenario($record),
            default => null,
        };

        if ($cascade === null) {
            return null;
        }

        $cascade['lineage'] = $this->lineage($modelName, $record, $cascade);

        return $cascade;
    }

    /**
     * Spine levels shown on the lineage rail (1 = why … 5 = how it is proven).
     *
     * @var array<int, list<string>>
     */
    protected const LEVELS = [
        1 => ['BusinessNeed'],
        2 => ['BusinessObjective'],
        3 => ['StakeholderNeed'],
        4 => ['Feature', 'FunctionalRequirement', 'NonFunctionalRequirement'],
        5 => ['Scenario'],
    ];

    /**
     * Why each gap matters, with the BABOK task it supports (shown on the Next step card).
     *
     * @var array<string, string>
     */
    protected const GAP_REASONS = [
        'no_objectives' => 'ui.lineage_why_no_objectives',
        'no_parent_need' => 'ui.lineage_why_no_parent_need',
        'no_stories' => 'ui.lineage_why_no_stories',
        'no_parent_objective' => 'ui.lineage_why_no_parent_objective',
        'no_packaging' => 'ui.lineage_why_no_packaging',
        'open_change_requests' => 'ui.lineage_why_open_change_requests',
        'no_parent_story' => 'ui.lineage_why_no_parent_story',
        'no_scenarios' => 'ui.lineage_why_no_scenarios',
        'no_parent_feature' => 'ui.lineage_why_no_parent_feature',
        'no_acceptance' => 'ui.lineage_why_no_acceptance',
    ];

    /**
     * Five-step lineage rail, the single most useful next step, and quick actions.
     *
     * @param  array<string, mixed>  $cascade
     * @return array{steps: list<array<string, mixed>>, complete: int, total: int, next: array<string, mixed>|null, others: list<array<string, mixed>>, quick: list<array<string, mixed>>, also: list<array<string, mixed>>}
     */
    protected function lineage(string $modelName, Model $record, array $cascade): array
    {
        $current = $this->levelOf($modelName);
        $gaps = $cascade['gaps'] ?? [];

        // FR / NFR: acceptance criteria are the proof step.
        if (in_array($modelName, ['FunctionalRequirement', 'NonFunctionalRequirement'], true)
            && blank($record->getAttribute('acceptance_criteria'))) {
            $gaps[] = $this->gap(
                'no_acceptance',
                __('ui.lineage_gap_no_acceptance'),
                __('ui.lineage_write_criteria'),
                model_modal_path($modelName, 'edit', $record->getKey()),
                $modelName,
                'update',
            );
        }
        // Stakeholder need with no solution packaging yet.
        if ($modelName === 'StakeholderNeed'
            && ! in_array('no_packaging', array_column($gaps, 'key'), true)
            && $record->functionalRequirements->isEmpty()
            && $record->nonFunctionalRequirements->isEmpty()
            && $record->features->isEmpty()) {
            $gaps[] = $this->gap(
                'no_packaging',
                __('ui.cascade_gap_no_packaging'),
                __('ui.add_functional_requirement'),
                $this->createUrl('FunctionalRequirement', ['stakeholder_need_id' => $record->getKey()]),
                'FunctionalRequirement',
            );
        }

        $gapsByKey = [];
        foreach ($gaps as $gap) {
            $gapsByKey[$gap['key']] = $gap;
        }

        $parentsByLevel = [];
        $also = [];
        foreach ($cascade['parents'] ?? [] as $parent) {
            $level = $this->levelOf((string) $parent['model']);
            if ($level === null) {
                $also[] = $parent; // e.g. originating change request
                continue;
            }
            $parentsByLevel[$level] = $parent;
        }

        $steps = [];
        foreach (self::LEVELS as $level => $models) {
            $step = [
                'level' => $level,
                'name' => __('ui.lineage_level_'.$level),
                'state' => 'later',
                'link' => null,
                'count' => null,
                'note' => null,
                'action' => null,
            ];

            if ($level < $current) {
                if (isset($parentsByLevel[$level])) {
                    $step['state'] = 'done';
                    $step['link'] = $parentsByLevel[$level];
                } elseif (! isset($parentsByLevel[$level + 1]) && $level + 1 < $current) {
                    // A higher parent can only follow once the nearer link exists.
                    $step['state'] = 'blocked';
                    $step['note'] = __('ui.lineage_follows', ['step' => $level + 1]);
                } else {
                    $step['state'] = 'missing';
                    $step['note'] = __('ui.lineage_not_linked');
                    $step['action'] = $this->gapAction($gapsByKey, ['no_parent_need', 'no_parent_objective', 'no_parent_story', 'no_parent_feature']);
                    if ($step['action'] !== null) {
                        $step['action']['label'] = __('ui.lineage_link_level_'.$level);
                    }
                }
            } elseif ($level === $current) {
                $step['state'] = 'current';
                $step['name'] = $this->entityName($modelName);
                $step['link'] = [
                    'code' => $record->getAttribute('code'),
                    'title' => trim((string) ($record->getAttribute('title') ?? '')),
                ];
            } elseif ($level === $current + 1 || ($current === 3 && $level === 5)) {
                [$count, $gapKey, $label] = $this->downstream($modelName, $record, $level, $cascade);
                if ($count > 0) {
                    $step['state'] = 'done';
                    $step['count'] = $count;
                    $step['note'] = $label;
                } else {
                    $gap = $gapKey !== null ? ($gapsByKey[$gapKey] ?? null) : null;
                    $step['state'] = $gap !== null ? 'missing' : 'optional';
                    $step['note'] = $label;
                    $step['action'] = $gap !== null ? $this->actionFromGap($gap) : null;
                }
            }

            if ($level === 4 && $current === 4) {
                $step['name'] = $this->entityName($modelName);
            }

            $steps[] = $step;
        }

        // Siblings: other solution items under the same stakeholder need.
        if ($current === 4 && isset($parentsByLevel[3]) && $record->getAttribute('stakeholder_need_id')) {
            $class = $record::class;
            $siblings = $class::query()
                ->where('stakeholder_need_id', $record->getAttribute('stakeholder_need_id'))
                ->whereKeyNot($record->getKey())
                ->count();
            if ($siblings > 0) {
                foreach ($steps as &$step) {
                    if ($step['state'] === 'current') {
                        $step['note'] = trans_choice('ui.lineage_siblings', $siblings, [
                            'count' => $siblings,
                            'parent' => $parentsByLevel[3]['code'] ?? '',
                        ]);
                    }
                }
                unset($step);
            }
        }

        $relevant = array_filter($steps, fn ($s) => in_array($s['state'], ['done', 'missing', 'current'], true));
        $done = count(array_filter($relevant, fn ($s) => in_array($s['state'], ['done', 'current'], true)));

        $actionable = array_values(array_filter(
            $gaps,
            fn (array $gap) => ! empty($gap['action_url'])
                && (empty($gap['action_model']) || entity_can((string) $gap['action_model'], (string) ($gap['action_ability'] ?? 'create'))),
        ));
        $next = null;
        if ($actionable !== []) {
            $first = $actionable[0];
            $parentLevel = ['no_parent_need' => 1, 'no_parent_objective' => 2, 'no_parent_story' => 3, 'no_parent_feature' => 4][$first['key']] ?? null;
            $nextAction = $this->actionFromGap($first);
            if ($nextAction !== null && $parentLevel !== null) {
                $nextAction['label'] = __('ui.lineage_link_level_'.$parentLevel);
            }
            $next = [
                'title' => $first['label'],
                'why' => isset(self::GAP_REASONS[$first['key']]) ? __(self::GAP_REASONS[$first['key']]) : null,
                'action' => $nextAction,
            ];
        }

        return [
            'steps' => $steps,
            'complete' => $done,
            'total' => count($relevant),
            'next' => $next,
            'others' => array_map(fn (array $gap) => [
                'title' => $gap['label'],
                'action' => $this->actionFromGap($gap),
            ], array_slice($actionable, 1)),
            'quick' => $this->quickActions($modelName, $record),
            'also' => $also,
        ];
    }

    protected function levelOf(string $model): ?int
    {
        foreach (self::LEVELS as $level => $models) {
            if (in_array($model, $models, true)) {
                return $level;
            }
        }

        return null;
    }

    protected function entityName(string $model): string
    {
        $label = (string) (CrudEntityRegistry::all()[$model]['nav_label'] ?? \Illuminate\Support\Str::headline($model));

        return \Illuminate\Support\Str::singular($label);
    }

    /**
     * @param  array<string, mixed>  $cascade
     * @return array{0: int, 1: string|null, 2: string}
     */
    protected function downstream(string $model, Model $record, int $level, array $cascade): array
    {
        $groupCount = function (string $key) use ($cascade): int {
            foreach ($cascade['groups'] ?? [] as $group) {
                if (($group['key'] ?? null) === $key) {
                    return count($group['items'] ?? []);
                }
            }

            return 0;
        };

        return match (true) {
            $model === 'BusinessNeed' => [$c = $groupCount('objectives'), 'no_objectives', trans_choice('ui.lineage_count_objectives', $c, ['count' => $c])],
            $model === 'BusinessObjective' => [$c = $groupCount('stories'), 'no_stories', trans_choice('ui.lineage_count_stories', $c, ['count' => $c])],
            $model === 'StakeholderNeed' && $level === 4 => [$c = $groupCount('packaging'), 'no_packaging', trans_choice('ui.lineage_count_packaging', $c, ['count' => $c])],
            $model === 'StakeholderNeed' && $level === 5 => [$c = $record->coveringScenarios()->count(), null, trans_choice('ui.lineage_count_scenarios', $c, ['count' => $c])],
            $model === 'Feature' => [$c = $record->scenarios->count(), 'no_scenarios', trans_choice('ui.lineage_count_scenarios', $c, ['count' => $c])],
            in_array($model, ['FunctionalRequirement', 'NonFunctionalRequirement'], true) => [
                $written = blank($record->getAttribute('acceptance_criteria')) ? 0 : 1,
                'no_acceptance',
                $written ? __('ui.lineage_acceptance_written') : __('ui.lineage_acceptance_missing'),
            ],
            default => [0, null, ''],
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $gapsByKey
     * @param  list<string>  $keys
     * @return array{label: string, url: string}|null
     */
    protected function gapAction(array $gapsByKey, array $keys): ?array
    {
        foreach ($keys as $key) {
            if (isset($gapsByKey[$key])) {
                return $this->actionFromGap($gapsByKey[$key]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $gap
     * @return array{label: string, url: string}|null
     */
    protected function actionFromGap(array $gap): ?array
    {
        if (empty($gap['action_url']) || blank($gap['action_label'] ?? null)) {
            return null;
        }
        if (! empty($gap['action_model']) && ! entity_can((string) $gap['action_model'], (string) ($gap['action_ability'] ?? 'create'))) {
            return null;
        }

        return ['label' => (string) $gap['action_label'], 'url' => (string) $gap['action_url']];
    }

    /**
     * "Raise a risk / change request / derive …" — each opens pre-linked to this item's need.
     *
     * @return list<array{label: string, url: string, icon: string}>
     */
    protected function quickActions(string $model, Model $record): array
    {
        $needId = match ($model) {
            'StakeholderNeed' => (int) $record->getKey(),
            'Feature', 'FunctionalRequirement', 'NonFunctionalRequirement' => (int) ($record->getAttribute('stakeholder_need_id') ?? 0),
            'Scenario' => (int) ($record->feature?->stakeholder_need_id ?? 0),
            default => 0,
        };

        $candidates = [
            ['Risk', __('ui.lineage_quick_risk'), 'shield-cross', []],
            ['ChangeRequest', __('ui.lineage_quick_cr'), 'arrow-mix', ['stakeholder_need_id' => $needId]],
        ];

        if ($model === 'FunctionalRequirement' && $needId > 0) {
            $candidates[] = ['NonFunctionalRequirement', __('ui.lineage_quick_nfr'), 'setting-2', ['stakeholder_need_id' => $needId]];
        }
        if ($model === 'Feature') {
            $candidates[] = ['Scenario', __('ui.add_scenario'), 'check-squared', ['feature_id' => (int) $record->getKey(), 'stakeholder_need_id' => $needId]];
        }

        $actions = [];
        foreach ($candidates as [$entity, $label, $icon, $query]) {
            if (! array_key_exists($entity, CrudEntityRegistry::all()) || ! entity_can($entity, 'create')) {
                continue;
            }
            $actions[] = ['label' => $label, 'url' => $this->createUrl($entity, $query), 'icon' => $icon];
        }

        return $actions;
    }

    /**
     * @return array<int|string, mixed>|null
     */
    protected function eagerLoad(string $modelName): ?array
    {
        $objectives = fn ($query) => $query->orderBy('number');
        $needs = fn ($query) => $query->orderBy('number');
        $features = fn ($query) => $query->withCount('scenarios')->orderBy('number');
        $frs = fn ($query) => $query->orderBy('number');
        $nfrs = fn ($query) => $query->orderBy('number');

        return match ($modelName) {
            'BusinessNeed' => ['businessObjectives' => $objectives],
            'BusinessObjective' => [
                'businessNeeds',
                'stakeholderNeeds' => $needs,
            ],
            'StakeholderNeed' => [
                'businessObjectives.businessNeeds',
                'features' => $features,
                'functionalRequirements' => $frs,
                'nonFunctionalRequirements' => $nfrs,
                'changeRequests',
            ],
            'Feature' => [
                'stakeholderNeed.businessObjectives.businessNeeds',
                'changeRequest.stakeholderNeed.businessObjectives.businessNeeds',
                'scenarios',
            ],
            'FunctionalRequirement', 'NonFunctionalRequirement' => [
                'stakeholderNeed.businessObjectives.businessNeeds',
                'changeRequest.stakeholderNeed.businessObjectives.businessNeeds',
            ],
            'Scenario' => [
                'feature.stakeholderNeed.businessObjectives.businessNeeds',
                'feature.changeRequest.stakeholderNeed.businessObjectives.businessNeeds',
            ],
            default => null,
        };
    }

    /**
     * @return array{current_label: string, parents: list<CascadeLink>, gaps: list<CascadeGap>, groups: list<CascadeGroup>}
     */
    protected function forNeed(BusinessNeed $need): array
    {
        $objectives = $need->businessObjectives;
        $addUrl = $this->createUrl('BusinessObjective', [
            'project_id' => (int) $need->project_id,
            'primary_business_need_id' => (int) $need->id,
        ]);

        $gaps = [];
        if ($objectives->isEmpty()) {
            $gaps[] = $this->gap(
                'no_objectives',
                __('ui.cascade_gap_no_objectives'),
                __('ui.add_objective'),
                $addUrl,
                'BusinessObjective',
            );
        }

        return $this->pack(
            $this->label($need),
            [],
            $gaps,
            [
                $this->group(
                    'objectives',
                    __('ui.business_objectives'),
                    __('ui.cascade_empty_objectives'),
                    __('ui.add_objective'),
                    $addUrl,
                    'BusinessObjective',
                    $this->links('BusinessObjective', $objectives),
                ),
            ],
        );
    }

    /**
     * @return array{current_label: string, parents: list<CascadeLink>, gaps: list<CascadeGap>, groups: list<CascadeGroup>}
     */
    protected function forObjective(BusinessObjective $objective): array
    {
        $primaryNeed = $this->primaryNeed($objective);
        $stories = $objective->stakeholderNeeds;
        $addUrl = $this->createUrl('StakeholderNeed', [
            'project_id' => (int) $objective->project_id,
            'business_objective_id' => (int) $objective->id,
        ]);

        $gaps = [];
        if ($primaryNeed === null) {
            $gaps[] = $this->gap(
                'no_parent_need',
                __('ui.cascade_gap_no_parent_need'),
                __('ui.edit'),
                model_modal_path('BusinessObjective', 'edit', $objective->id),
                'BusinessObjective',
                'update',
            );
        }
        if ($stories->isEmpty()) {
            $gaps[] = $this->gap(
                'no_stories',
                __('ui.cascade_gap_no_stories'),
                __('ui.add_stakeholder_need'),
                $addUrl,
                'StakeholderNeed',
            );
        }

        $parents = $primaryNeed ? [$this->link('BusinessNeed', $primaryNeed)] : [];

        return $this->pack(
            $this->label($objective),
            $parents,
            $gaps,
            [
                $this->group(
                    'stories',
                    __('ui.stakeholder_needs'),
                    __('ui.cascade_empty_stories'),
                    __('ui.add_stakeholder_need'),
                    $addUrl,
                    'StakeholderNeed',
                    $this->links('StakeholderNeed', $stories),
                ),
            ],
        );
    }

    /**
     * @return array{current_label: string, parents: list<CascadeLink>, gaps: list<CascadeGap>, groups: list<CascadeGroup>}
     */
    protected function forStakeholderNeed(StakeholderNeed $need): array
    {
        $projectId = (int) $need->project_id;
        $features = $need->features;
        $frs = $need->functionalRequirements;
        $nfrs = $need->nonFunctionalRequirements;
        $openCrs = $need->changeRequests
            ->filter(fn (ChangeRequest $cr): bool => in_array((string) $cr->status, [
                ChangeRequestStatus::DRAFT,
                ChangeRequestStatus::UNDER_REVIEW,
            ], true))
            ->values();

        $addFeature = $this->createUrl('Feature', [
            'project_id' => $projectId,
            'stakeholder_need_id' => (int) $need->id,
        ]);
        $addFr = $this->createUrl('FunctionalRequirement', [
            'project_id' => $projectId,
            'stakeholder_need_id' => (int) $need->id,
        ]);
        $addNfr = $this->createUrl('NonFunctionalRequirement', [
            'project_id' => $projectId,
            'stakeholder_need_id' => (int) $need->id,
        ]);

        $gaps = [];
        if ($need->businessObjectives->isEmpty()) {
            $gaps[] = $this->gap(
                'no_parent_objective',
                __('ui.cascade_gap_no_parent_objective'),
                __('ui.edit'),
                model_modal_path('StakeholderNeed', 'edit', $need->id),
                'StakeholderNeed',
                'update',
            );
        }
        if ($openCrs->isNotEmpty()) {
            $gaps[] = $this->gap(
                'open_change_requests',
                __('ui.cascade_gap_open_change_requests', ['count' => $openCrs->count()]),
                __('ui.request_change'),
                $this->createUrl('ChangeRequest', [
                    'project_id' => $projectId,
                    'stakeholder_need_id' => (int) $need->id,
                ]),
                'ChangeRequest',
                'create',
                $this->links('ChangeRequest', $openCrs),
            );
        }

        $featureLinks = $features->map(function (Feature $feature) {
            $count = (int) ($feature->scenarios_count ?? $feature->scenarios->count());
            $link = $this->link('Feature', $feature);
            $link['kind'] = __('ui.cascade_kind_feature');
            $link['meta'] = $count === 0
                ? __('ui.cascade_meta_no_scenarios')
                : __('ui.cascade_meta_scenarios', ['count' => $count]);

            return $link;
        })->values()->all();

        $frLinks = array_map(function (array $link) {
            $link['kind'] = __('ui.functional_requirement_short');

            return $link;
        }, $this->links('FunctionalRequirement', $frs));

        $nfrLinks = array_map(function (array $link) {
            $link['kind'] = __('ui.non_functional_requirement_short');

            return $link;
        }, $this->links('NonFunctionalRequirement', $nfrs));

        return $this->pack(
            $this->label($need),
            $this->stakeholderNeedAncestors($need),
            $gaps,
            [
                $this->group(
                    'packaging',
                    __('ui.cascade_packaging'),
                    __('ui.cascade_empty_packaging'),
                    '',
                    '',
                    '',
                    array_merge($frLinks, $featureLinks, $nfrLinks),
                    [
                        $this->addAction(__('ui.add_functional_requirement'), $addFr, 'FunctionalRequirement'),
                        $this->addAction(__('ui.add_feature'), $addFeature, 'Feature'),
                        $this->addAction(__('ui.add_nfr'), $addNfr, 'NonFunctionalRequirement'),
                    ],
                ),
            ],
        );
    }

    /**
     * @return array{current_label: string, parents: list<CascadeLink>, gaps: list<CascadeGap>, groups: list<CascadeGroup>}
     */
    protected function forFeature(Feature $feature): array
    {
        $addUrl = $this->createUrl('Scenario', [
            'feature_id' => (int) $feature->id,
        ]);

        $gaps = [];
        if ($feature->stakeholderNeed === null && $feature->changeRequest === null) {
            $gaps[] = $this->gap(
                'no_parent_story',
                __('ui.cascade_gap_no_parent_story'),
                __('ui.edit'),
                model_modal_path('Feature', 'edit', $feature->id),
                'Feature',
                'update',
            );
        }
        if ($feature->scenarios->isEmpty()) {
            $gaps[] = $this->gap(
                'no_scenarios',
                __('ui.cascade_gap_no_scenarios'),
                __('ui.add_scenario'),
                $addUrl,
                'Scenario',
            );
        }

        return $this->pack(
            $this->label($feature),
            $this->solutionPackagingAncestors($feature),
            $gaps,
            [],
        );
    }

    /**
     * @return array{current_label: string, parents: list<CascadeLink>, gaps: list<CascadeGap>, groups: list<CascadeGroup>}
     */
    protected function forFunctionalRequirement(FunctionalRequirement $requirement): array
    {
        $gaps = [];
        if ($requirement->stakeholderNeed === null && $requirement->changeRequest === null) {
            $gaps[] = $this->gap(
                'no_parent_story',
                __('ui.cascade_gap_no_parent_story'),
                __('ui.edit'),
                model_modal_path('FunctionalRequirement', 'edit', $requirement->id),
                'FunctionalRequirement',
                'update',
            );
        }

        return $this->pack(
            $this->label($requirement),
            $this->solutionPackagingAncestors($requirement),
            $gaps,
            [],
        );
    }

    /**
     * @return array{current_label: string, parents: list<CascadeLink>, gaps: list<CascadeGap>, groups: list<CascadeGroup>}
     */
    protected function forNonFunctionalRequirement(NonFunctionalRequirement $requirement): array
    {
        $gaps = [];
        if ($requirement->stakeholderNeed === null && $requirement->changeRequest === null) {
            $gaps[] = $this->gap(
                'no_parent_story',
                __('ui.cascade_gap_no_parent_story'),
                __('ui.edit'),
                model_modal_path('NonFunctionalRequirement', 'edit', $requirement->id),
                'NonFunctionalRequirement',
                'update',
            );
        }

        return $this->pack(
            $this->label($requirement),
            $this->solutionPackagingAncestors($requirement),
            $gaps,
            [],
        );
    }

    /**
     * @return array{current_label: string, parents: list<CascadeLink>, gaps: list<CascadeGap>, groups: list<CascadeGroup>}
     */
    protected function forScenario(Scenario $scenario): array
    {
        $feature = $scenario->feature;
        $parents = [];
        if ($feature !== null) {
            $parents = $this->solutionPackagingAncestors($feature);
            $parents[] = $this->link('Feature', $feature);
        }

        $gaps = [];
        if ($feature === null) {
            $gaps[] = $this->gap(
                'no_parent_feature',
                __('ui.cascade_gap_no_parent_feature'),
                __('ui.edit'),
                model_modal_path('Scenario', 'edit', $scenario->id),
                'Scenario',
                'update',
            );
        }

        $title = $scenario->gherkinKeyword().': '.$scenario->title;

        return $this->pack($title, $parents, $gaps, []);
    }

    /**
     * @return list<CascadeLink>
     */
    protected function stakeholderNeedAncestors(StakeholderNeed $need): array
    {
        $objective = $need->businessObjectives->first();
        if ($objective === null) {
            return [];
        }

        $parents = [];
        $primaryNeed = $this->primaryNeed($objective);
        if ($primaryNeed !== null) {
            $parents[] = $this->link('BusinessNeed', $primaryNeed);
        }
        $parents[] = $this->link('BusinessObjective', $objective);

        return $parents;
    }

    /**
     * @param  Feature|FunctionalRequirement|NonFunctionalRequirement  $record
     * @return list<CascadeLink>
     */
    protected function solutionPackagingAncestors(Model $record): array
    {
        $need = $record->stakeholderNeed;
        $changeRequest = $record->changeRequest;
        if ($need === null && $changeRequest !== null) {
            $need = $changeRequest->stakeholderNeed;
        }

        $parents = [];
        if ($need !== null) {
            $parents = $this->stakeholderNeedAncestors($need);
            $parents[] = $this->link('StakeholderNeed', $need);
        }
        if ($changeRequest !== null && (int) ($record->change_request_id ?? 0) > 0) {
            $parents[] = $this->link('ChangeRequest', $changeRequest);
        }

        return $parents;
    }

    protected function primaryNeed(BusinessObjective $objective): ?BusinessNeed
    {
        $needs = $objective->relationLoaded('businessNeeds')
            ? $objective->businessNeeds
            : $objective->businessNeeds()->get();

        return $needs->firstWhere('pivot.is_primary', true)
            ?? $needs->first();
    }

    /**
     * @param  list<CascadeLink>  $parents
     * @param  list<CascadeGap>  $gaps
     * @param  list<CascadeGroup>  $groups
     * @return array{current_label: string, parents: list<CascadeLink>, gaps: list<CascadeGap>, groups: list<CascadeGroup>}
     */
    protected function pack(string $currentLabel, array $parents, array $gaps, array $groups): array
    {
        return [
            'current_label' => $currentLabel,
            'parents' => $parents,
            'gaps' => $gaps,
            'groups' => $groups,
        ];
    }

    /**
     * @param  list<CascadeLink>  $links
     * @return CascadeGap
     */
    protected function gap(
        string $key,
        string $label,
        ?string $actionLabel,
        ?string $actionUrl,
        ?string $actionModel = null,
        string $actionAbility = 'create',
        array $links = [],
    ): array {
        $gap = [
            'key' => $key,
            'label' => $label,
            'action_label' => $actionLabel,
            'action_url' => $actionUrl,
            'action_model' => $actionModel,
            'action_ability' => $actionAbility,
        ];
        if ($links !== []) {
            $gap['links'] = $links;
        }

        return $gap;
    }

    /**
     * @param  list<CascadeLink>  $items
     * @param  list<CascadeAddAction>  $addActions
     * @return CascadeGroup
     */
    protected function group(
        string $key,
        string $heading,
        string $empty,
        string $addLabel,
        string $addUrl,
        string $addModel,
        array $items,
        array $addActions = [],
    ): array {
        $group = [
            'key' => $key,
            'heading' => $heading,
            'empty' => $empty,
            'add_label' => $addLabel,
            'add_url' => $addUrl,
            'add_model' => $addModel,
            'items' => $items,
        ];
        if ($addActions !== []) {
            $group['add_actions'] = $addActions;
        }

        return $group;
    }

    /**
     * @return CascadeAddAction
     */
    protected function addAction(string $label, string $url, string $model): array
    {
        return [
            'label' => $label,
            'url' => $url,
            'model' => $model,
        ];
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return list<CascadeLink>
     */
    protected function links(string $model, Collection $records): array
    {
        return $records->map(fn (Model $record) => $this->link($model, $record))->values()->all();
    }

    /**
     * @return CascadeLink
     */
    protected function link(string $model, Model $record): array
    {
        $code = $record->code ?? null;
        $title = trim((string) ($record->title ?? $record->name ?? ''));

        return [
            'label' => $this->label($record),
            'code' => is_string($code) && $code !== '' ? $code : null,
            'title' => $title !== '' ? $title : null,
            'model' => $model,
            'url' => model_route($model, 'show', $record->getKey()),
            'modal_url' => model_modal_path($model, 'view', $record->getKey()),
        ];
    }

    protected function label(Model $record): string
    {
        $title = trim((string) ($record->title ?? $record->name ?? ''));
        $code = $record->code ?? null;
        if (is_string($code) && $code !== '') {
            return $title !== '' ? $code.' — '.$title : $code;
        }

        return $title;
    }

    /**
     * @param  array<string, int|string|null>  $query
     */
    protected function createUrl(string $model, array $query): string
    {
        $query = array_filter(
            $query,
            fn ($value) => $value !== null && $value !== '' && $value !== 0 && $value !== '0'
        );
        $base = model_modal_path($model, 'create');

        return $query === [] ? $base : $base.'?'.http_build_query($query);
    }
}
