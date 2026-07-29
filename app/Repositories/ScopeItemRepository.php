<?php

namespace App\Repositories;

use App\Data\ScopeItemData;
use App\Data\ScopeItemViewData;
use App\Models\ScopeItem;
use Illuminate\Database\Eloquent\Model;

class ScopeItemRepository extends BaseRepository
{
    public Model $model;

    public $editDto = ScopeItemData::class;

    public $viewDto = ScopeItemViewData::class;

    protected array $listFilters = [
        'project_id',
        'direction',
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
        $this->model = new ScopeItem();
    }
}
