<?php

namespace App\Http\Controllers;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\Concerns\HasEntityStatus;
use App\Models\ChangeRequest;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Models\Project;
use App\Models\Risk;
use App\Models\StakeholderNeed;
use App\Services\ProjectReadinessService;
use App\Support\ChangeRequestStatus;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use App\Support\EntityStatus;
use App\Support\RiskImpact;
use App\Support\RiskLikelihood;
use App\Support\RiskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * "My work" landing page: projects with readiness, items awaiting decisions, recent activity.
 */
class HomeController extends Controller
{
    /** Projects shown with a live readiness score (each costs a readiness pass). */
    protected const PROJECT_LIMIT = 6;

    protected const ACTIVITY_LIMIT = 10;

    /**
     * Spine entities surfaced in "Recent activity".
     *
     * @var array<string, class-string<Model>>
     */
    protected const ACTIVITY_MODELS = [
        'BusinessNeed' => BusinessNeed::class,
        'BusinessObjective' => BusinessObjective::class,
        'StakeholderNeed' => StakeholderNeed::class,
        'Feature' => Feature::class,
        'FunctionalRequirement' => FunctionalRequirement::class,
        'NonFunctionalRequirement' => NonFunctionalRequirement::class,
        'ChangeRequest' => ChangeRequest::class,
        'Risk' => Risk::class,
    ];

    public function __construct(protected ProjectReadinessService $readiness) {}

    public function index(): View
    {
        $canProjects = entity_can('Project', EntityAccess::VIEW);

        $projects = collect();
        $projectTotal = 0;
        if ($canProjects) {
            $projectTotal = Project::query()->count();
            $projects = Project::query()
                ->with(['workspace', 'status'])
                ->latest('updated_at')
                ->limit(self::PROJECT_LIMIT)
                ->get()
                ->map(function (Project $project): array {
                    $readiness = $this->readiness->forProject($project);

                    return [
                        'project' => $project,
                        'score' => $readiness['score'],
                        'gaps' => $readiness['total_gaps'],
                        'critical' => $readiness['severity']['critical'] ?? 0,
                        'folders' => array_values(array_filter(
                            $readiness['folders'] ?? [],
                            fn (array $folder) => $folder['checks'] > 0,
                        )),
                    ];
                });
        }

        return view('pages.home', [
            'user' => auth()->user(),
            'projects' => $projects,
            'projectTotal' => $projectTotal,
            'kpis' => $this->kpis($projectTotal, $canProjects),
            'awaiting' => $this->awaitingDecision(),
            'activity' => $this->recentActivity(),
            'reviews' => app(\App\Services\ApprovalService::class)->awaitingReview()
                ->map(fn (Model $m) => $this->row(class_basename($m), $m)),
            'mentions' => auth()->user() ? app(\App\Services\CommentService::class)->mentionsFor(auth()->user()) : collect(),
        ]);
    }

    /**
     * @return list<array{label: string, value: int, icon: string, tone: string, url: string|null, hint: string}>
     */
    protected function kpis(int $projectTotal, bool $canProjects): array
    {
        $kpis = [];

        if ($canProjects) {
            $kpis[] = [
                'label' => __('ui.home_kpi_projects'),
                'value' => $projectTotal,
                'icon' => entity_icon('Project', 'abstract-26'),
                'tone' => 'info',
                'url' => model_route('Project', 'index'),
                'hint' => __('ui.home_kpi_projects_hint'),
            ];
        }

        if (entity_can('ChangeRequest', EntityAccess::VIEW)) {
            $kpis[] = [
                'label' => __('ui.home_kpi_open_crs'),
                'value' => ChangeRequest::query()
                    ->whereIn('status', [ChangeRequestStatus::DRAFT, ChangeRequestStatus::UNDER_REVIEW])
                    ->count(),
                'icon' => entity_icon('ChangeRequest', 'arrow-mix'),
                'tone' => 'warning',
                'url' => model_route('ChangeRequest', 'index'),
                'hint' => __('ui.home_kpi_open_crs_hint'),
            ];
        }

        if (entity_can('Risk', EntityAccess::VIEW)) {
            $kpis[] = [
                'label' => __('ui.home_kpi_critical_risks'),
                'value' => Risk::query()
                    ->where('likelihood', RiskLikelihood::HIGH)
                    ->where('impact', RiskImpact::HIGH)
                    ->whereIn('status', RiskStatus::active())
                    ->count(),
                'icon' => entity_icon('Risk', 'shield-cross'),
                'tone' => 'danger',
                'url' => model_route('Risk', 'index'),
                'hint' => __('ui.home_kpi_critical_risks_hint'),
            ];
        }

        $needRevisionId = EntityStatus::id(EntityStatus::NEED_REVISION);
        if ($needRevisionId !== null) {
            $count = 0;
            foreach ([Feature::class => 'Feature', FunctionalRequirement::class => 'FunctionalRequirement', NonFunctionalRequirement::class => 'NonFunctionalRequirement'] as $class => $entity) {
                if (entity_can($entity, EntityAccess::VIEW)) {
                    $count += $class::query()->where('status_id', $needRevisionId)->count();
                }
            }
            $kpis[] = [
                'label' => __('ui.home_kpi_need_revision'),
                'value' => $count,
                'icon' => 'pencil',
                'tone' => 'warning',
                'url' => route('solution_requirements.index'),
                'hint' => __('ui.home_kpi_need_revision_hint'),
            ];
        }

        return $kpis;
    }

    /**
     * Change requests waiting for a decision (under review first, oldest first).
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function awaitingDecision(): Collection
    {
        if (! entity_can('ChangeRequest', EntityAccess::VIEW)) {
            return collect();
        }

        return ChangeRequest::query()
            ->with('project')
            ->whereIn('status', [ChangeRequestStatus::UNDER_REVIEW, ChangeRequestStatus::DRAFT])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [ChangeRequestStatus::UNDER_REVIEW])
            ->oldest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (ChangeRequest $cr) => $this->row('ChangeRequest', $cr));
    }

    /**
     * Most recently touched spine items across all visible projects.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function recentActivity(): Collection
    {
        $rows = collect();

        foreach (self::ACTIVITY_MODELS as $entity => $class) {
            if (! entity_can($entity, EntityAccess::VIEW)) {
                continue;
            }

            $with = in_array(HasEntityStatus::class, class_uses_recursive($class), true)
                ? ['project', 'status']
                : ['project'];

            $class::query()
                ->with($with)
                ->latest('updated_at')
                ->limit(self::ACTIVITY_LIMIT)
                ->get()
                ->each(function (Model $model) use ($entity, $rows): void {
                    $rows->push($this->row($entity, $model));
                });
        }

        return $rows
            ->sortByDesc(fn (array $row) => $row['updated_at']?->getTimestamp() ?? 0)
            ->take(self::ACTIVITY_LIMIT)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(string $entity, Model $model): array
    {
        $status = $model->getAttribute('status');
        if ($status instanceof Model) {
            $status = $status->getAttribute('name');
        } elseif (is_string($status) && $status !== '') {
            $status = Str::headline($status);
        }

        $options = CrudEntityRegistry::all()[$entity] ?? [];

        return [
            'entity' => $entity,
            'entity_label' => Str::singular((string) ($options['nav_label'] ?? Str::headline($entity))),
            'icon' => entity_icon($entity),
            'code' => $model->getAttribute('code'),
            'title' => (string) ($model->getAttribute('title') ?? ''),
            'status' => is_string($status) ? $status : null,
            'project' => $model->getAttribute('project')?->name,
            'updated_at' => $model->getAttribute('updated_at'),
            'url' => model_route($entity, 'show', $model->getKey()),
            'modal' => model_modal_path($entity, 'view', $model->getKey()),
        ];
    }
}
