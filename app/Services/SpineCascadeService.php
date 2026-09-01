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
 * @phpstan-type CascadeLink array{label: string, url: string, modal_url: string, meta?: string}
     * @phpstan-type CascadeGap array{key: string, label: string, action_label: string|null, action_url: string|null, action_model: string|null, action_ability: string, links?: list<CascadeLink>}
 * @phpstan-type CascadeGroup array{key: string, heading: string, empty: string, add_label: string, add_url: string, add_model: string, items: list<CascadeLink>}
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

        return match ($modelName) {
            'BusinessNeed' => $this->forNeed($record),
            'BusinessObjective' => $this->forObjective($record),
            'StakeholderNeed' => $this->forStakeholderNeed($record),
            'Feature' => $this->forFeature($record),
            'FunctionalRequirement' => $this->forFunctionalRequirement($record),
            'NonFunctionalRequirement' => $this->forNonFunctionalRequirement($record),
            'Scenario' => $this->forScenario($record),
            default => null,
        };
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
        if ($features->isEmpty() && $frs->isEmpty() && $nfrs->isEmpty()) {
            $gaps[] = $this->gap(
                'no_packaging',
                __('ui.cascade_gap_no_packaging'),
                __('ui.add_feature'),
                $addFeature,
                'Feature',
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
            $link['meta'] = $count === 0
                ? __('ui.cascade_meta_no_scenarios')
                : __('ui.cascade_meta_scenarios', ['count' => $count]);

            return $link;
        })->values()->all();

        return $this->pack(
            $this->label($need),
            $this->stakeholderNeedAncestors($need),
            $gaps,
            [
                $this->group(
                    'features',
                    __('ui.features'),
                    __('ui.cascade_empty_features'),
                    __('ui.add_feature'),
                    $addFeature,
                    'Feature',
                    $featureLinks,
                ),
                $this->group(
                    'functional_requirements',
                    __('ui.functional_requirements'),
                    __('ui.cascade_empty_frs'),
                    __('ui.add_functional_requirement'),
                    $addFr,
                    'FunctionalRequirement',
                    $this->links('FunctionalRequirement', $frs),
                ),
                $this->group(
                    'non_functional_requirements',
                    __('ui.non_functional_requirements'),
                    __('ui.cascade_empty_nfrs'),
                    __('ui.add_nfr'),
                    $addNfr,
                    'NonFunctionalRequirement',
                    $this->links('NonFunctionalRequirement', $nfrs),
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
    ): array {
        return [
            'key' => $key,
            'heading' => $heading,
            'empty' => $empty,
            'add_label' => $addLabel,
            'add_url' => $addUrl,
            'add_model' => $addModel,
            'items' => $items,
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
        return [
            'label' => $this->label($record),
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
