<?php

namespace App\Repositories;

use App\Data\ScreenElementData;
use App\Data\ScreenElementViewData;
use App\Models\ScreenElement;
use Illuminate\Database\Eloquent\Model;

class ScreenElementRepository extends BaseRepository
{
    public Model $model;

    public $editDto = ScreenElementData::class;

    public $viewDto = ScreenElementViewData::class;

    protected array $listFilters = [
        'project_id',
        'screen_id',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
        'screen',
    ];

    public function __construct()
    {
        $this->model = new ScreenElement;
    }
}
