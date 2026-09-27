<?php

namespace App\Repositories;

use App\Data\FeatureData;
use App\Data\FeatureViewData;
use App\Models\Feature;
use App\Support\SolutionPackagingParent;
use Illuminate\Database\Eloquent\Model;

class FeatureRepository extends BaseRepository
{
    public Model $model;
    public $editDto = FeatureData::class;
    public $viewDto = FeatureViewData::class;

    protected array $listFilters = [
        'project_id',
        'status_id',
        'priority_id',
        'stakeholder_need_id',
        'change_request_id',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
        'changeRequest',
    ];

    protected array $listWithCounts = [
        'scenarios',
    ];

    public function __construct()
    {
        $this->model = new Feature();
    }

    public function create(array $data)
    {
        $data = SolutionPackagingParent::normalize($data);
        $feature = new Feature($this->filterFillable($data));
        $feature->syncDocumentFields();
        $feature->save();

        return $feature->fresh();
    }

    public function update($id, array $newData)
    {
        $newData = SolutionPackagingParent::normalize($newData);
        /** @var Feature $feature */
        $feature = Feature::query()->findOrFail($id);
        $feature->fill($this->filterFillable($newData));
        $feature->syncDocumentFields();
        $feature->save();

        return $feature->fresh();
    }

    public function findForDocument(int $id): Feature
    {
        /** @var Feature $feature */
        $feature = $this->findModel($id, [
            'scenarios' => fn ($query) => $query->with('stakeholderNeed')->orderBy('id'),
            'project',
            'stakeholderNeed',
            'changeRequest',
            'swimlaneFlowStep',
            'priority',
            'status',
        ]);

        return $feature;
    }

    /**
     * @return array<int, string>
     */
    public function optionsForProject(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        return Feature::query()
            ->where('project_id', $projectId)
            ->orderBy('number')
            ->orderBy('title')
            ->get(['id', 'number', 'title'])
            ->mapWithKeys(fn (Feature $f) => [
                $f->id => trim(($f->number ? $f->number.' — ' : '').$f->title),
            ])
            ->all();
    }
}
