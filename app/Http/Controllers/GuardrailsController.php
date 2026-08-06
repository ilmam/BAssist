<?php

namespace App\Http\Controllers;

use App\Models\Assumption;
use App\Models\BusinessRule;
use App\Models\Constraint;
use App\Models\Project;
use App\Support\AssumptionStatus;
use App\Support\BusinessRuleStatus;
use App\Support\ConstraintStatus;
use App\Support\EntityAccess;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuardrailsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeView();

        $tenantId = auth()->user()?->tenant_id;
        $projectId = $this->resolveProjectId($request);
        $workspaceId = $this->resolveWorkspaceId($request);

        $projects = Project::query()
            ->with('workspace')
            ->when($tenantId !== null, fn ($q) => $q->whereHas(
                'workspace',
                fn ($w) => $w->where('tenant_id', $tenantId)
            ))
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'workspace_id']);

        $sections = [];

        if (entity_can('Assumption', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'Assumption',
                label: __('ui.assumptions'),
                description: __('ui.guardrails_assumptions_help'),
                icon: entity_icon('Assumption'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
                statusLabelResolver: fn (object $item) => AssumptionStatus::label((string) $item->status),
            );
        }

        if (entity_can('Constraint', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'Constraint',
                label: __('ui.constraints'),
                description: __('ui.guardrails_constraints_help'),
                icon: entity_icon('Constraint'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
                statusLabelResolver: fn (object $item) => ConstraintStatus::label((string) $item->status),
            );
        }

        if (entity_can('BusinessRule', EntityAccess::VIEW)) {
            $sections[] = $this->section(
                model: 'BusinessRule',
                label: __('ui.business_rules'),
                description: __('ui.guardrails_business_rules_help'),
                icon: entity_icon('BusinessRule'),
                projectId: $projectId,
                workspaceId: $workspaceId,
                tenantId: $tenantId,
                statusLabelResolver: fn (object $item) => BusinessRuleStatus::label((string) $item->status),
            );
        }

        return view('pages.guardrails.index', [
            'sections' => $sections,
            'projects' => $projects,
            'filters' => [
                'project_id' => $projectId,
                'workspace_id' => $workspaceId,
            ],
        ]);
    }

    /**
     * @param  callable(object): string  $statusLabelResolver
     * @return array<string, mixed>
     */
    protected function section(
        string $model,
        string $label,
        string $description,
        string $icon,
        ?int $projectId,
        ?int $workspaceId,
        mixed $tenantId,
        callable $statusLabelResolver,
    ): array {
        $query = $this->baseQuery($model, $projectId, $workspaceId, $tenantId);

        $count = (clone $query)->count();
        $items = (clone $query)
            ->orderByDesc('updated_at')
            ->orderBy('title')
            ->limit(50)
            ->get()
            ->map(function (object $item) use ($statusLabelResolver) {
                $item->status_label = $statusLabelResolver($item);

                return $item;
            });

        $scopeQuery = array_filter([
            'workspace_id' => $workspaceId,
            'project_id' => $projectId,
        ], fn ($v) => $v !== null);

        $indexUrl = model_route($model, 'index');
        if ($scopeQuery !== []) {
            $indexUrl .= '?'.http_build_query($scopeQuery);
        }

        return [
            'model' => $model,
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'count' => $count,
            'items' => $items,
            'index_url' => $indexUrl,
            'create_modal_url' => entity_can($model, EntityAccess::CREATE)
                ? model_modal_path($model, 'create')
                : null,
            'can_create' => entity_can($model, EntityAccess::CREATE),
        ];
    }

    protected function baseQuery(string $model, ?int $projectId, ?int $workspaceId, mixed $tenantId): Builder
    {
        $query = match ($model) {
            'Assumption' => Assumption::query()->with('project'),
            'Constraint' => Constraint::query()->with('project'),
            'BusinessRule' => BusinessRule::query()->with('project'),
            default => throw new \InvalidArgumentException("Unknown guardrail model [{$model}]."),
        };

        return $query
            ->when($tenantId !== null, fn ($q) => $q->whereHas(
                'project.workspace',
                fn ($w) => $w->where('tenant_id', $tenantId)
            ))
            ->when($workspaceId !== null, fn ($q) => $q->whereHas(
                'project',
                fn ($p) => $p->where('workspace_id', $workspaceId)
            ))
            ->when($projectId !== null, fn ($q) => $q->where('project_id', $projectId));
    }

    protected function resolveProjectId(Request $request): ?int
    {
        $raw = $request->query('project_id');
        if ($raw !== null && $raw !== '') {
            return (int) $raw;
        }

        return app(ProjectContext::class)->id();
    }

    protected function resolveWorkspaceId(Request $request): ?int
    {
        $raw = $request->query('workspace_id');
        if ($raw !== null && $raw !== '') {
            return (int) $raw;
        }

        return app(WorkspaceContext::class)->id();
    }

    protected function authorizeView(): void
    {
        $user = auth()->user();

        $canView = EntityAccess::can($user, 'Assumption', EntityAccess::VIEW)
            || EntityAccess::can($user, 'Constraint', EntityAccess::VIEW)
            || EntityAccess::can($user, 'BusinessRule', EntityAccess::VIEW);

        if (! $canView) {
            EntityAccess::authorize($user, 'Assumption', EntityAccess::VIEW);
        }
    }
}
