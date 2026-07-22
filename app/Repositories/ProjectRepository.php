<?php

namespace App\Repositories;

use App\Data\ProjectData;
use App\Data\ProjectViewData;
use App\Models\Project;
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

    protected array $listWithCounts = [
        'businessObjectives',
        'businessNeeds',
        'stakeholders',
        'stakeholderNeeds',
    ];

    public function __construct()
    {
        $this->model = new Project();
    }
}
