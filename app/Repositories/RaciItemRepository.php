<?php

namespace App\Repositories;

use App\Data\RaciItemData;
use App\Data\RaciItemViewData;
use App\Models\RaciItem;
use Illuminate\Database\Eloquent\Model;

class RaciItemRepository extends BaseRepository
{
    public Model $model;
    public $editDto = RaciItemData::class;
    public $viewDto = RaciItemViewData::class;

    protected array $listFilters = [
        'project_id',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
    ];

    public function __construct()
    {
        $this->model = new RaciItem();
    }
}
