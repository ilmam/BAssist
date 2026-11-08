<?php

namespace App\Repositories;

use App\Data\ProjectData;
use App\Data\ProjectViewData;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectRepository extends BaseRepository
{
    public Model $model;
    public $editDto = ProjectData::class;
    public $viewDto = ProjectViewData::class;

    protected array $listFilters = [
        'workspace_id',
        'status_id',
    ];

    protected string|array|null $listTenantScope = ['workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'workspace',
    ];

    /**
     * brief_id: projects that came from one brief.
     * has_brief (yes / no) is applied in newListQuery(); informational, never a gap.
     */
    protected array $listRelationFilters = [
        'brief_id' => 'briefs',
    ];

    protected array $listWithCounts = [
        'briefs',
        'businessObjectives',
        'businessNeeds',
        'stakeholders',
        'stakeholderNeeds',
        'features',
        'stateFlows',
        'swimlaneFlows',
    ];

    public function __construct()
    {
        $this->model = new Project();
    }

    public function allowedListFilters(): array
    {
        return array_values(array_unique([...parent::allowedListFilters(), 'has_brief']));
    }

    protected function newListQuery(?array $filters = null): Builder
    {
        $query = parent::newListQuery($filters);

        match (strtolower((string) ($filters['has_brief'] ?? ''))) {
            'yes', '1', 'true' => $query->whereHas('briefs'),
            'no', '0', 'false' => $query->whereDoesntHave('briefs'),
            default => null,
        };

        return $query;
    }
}
