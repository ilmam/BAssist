<?php

namespace App\Repositories;

use App\Data\BriefData;
use App\Data\BriefViewData;
use App\Models\Brief;
use Illuminate\Database\Eloquent\Model;

class BriefRepository extends BaseRepository
{
    public Model $model;
    public $editDto = BriefData::class;
    public $viewDto = BriefViewData::class;

    protected array $listFilters = [
        'workspace_id',
        'status',
    ];

    /**
     * Briefs are owned by a workspace, which is owned by a tenant.
     */
    protected string|array|null $listTenantScope = ['workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'workspace',
    ];

    protected array $listWithCounts = [
        'projects',
    ];

    public function __construct()
    {
        $this->model = new Brief();
    }
}
