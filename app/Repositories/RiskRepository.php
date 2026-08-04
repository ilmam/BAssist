<?php

namespace App\Repositories;

use App\Data\RiskData;
use App\Data\RiskViewData;
use App\Models\Risk;
use App\Support\RiskResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RiskRepository extends BaseRepository
{
    public Model $model;

    public $editDto = RiskData::class;

    public $viewDto = RiskViewData::class;

    protected array $listFilters = [
        'project_id',
        'status',
        'category',
        'likelihood',
        'impact',
        'response',
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
        $this->model = new Risk();
    }

    public function getById($Id)
    {
        /** @var Risk $model */
        $model = $this->model::query()->with(['project.workspace'])->findOrFail($Id);

        return $this->toViewData($model);
    }

    public function getAll(?array $filters = null)
    {
        $collection = $this->getCollectionWithBelongsTo(true, null, $filters);

        return RiskViewData::collect(
            $collection->map(fn (Risk $model) => $this->toViewData($model))->all()
        );
    }

    public function create(array $data)
    {
        $this->assertGates($data);

        return parent::create($data);
    }

    public function update($id, array $newData)
    {
        /** @var Risk $existing */
        $existing = $this->model::query()->findOrFail($id);
        $merged = array_merge($existing->only([
            'likelihood',
            'impact',
            'response',
            'treatment',
            'status',
        ]), $newData);

        $this->assertGates($merged);

        return parent::update($id, $newData);
    }

    protected function toViewData(Risk $model): RiskViewData
    {
        $base = RiskViewData::from($model)->toArray();
        $base['score'] = (int) $model->score;
        $base['score_band'] = (string) $model->score_band;
        $base['score_label'] = (string) $model->score_label;
        $base['is_critical'] = $model->isCritical();
        $base['has_coverage_gap'] = $model->hasCoverageGap();

        return RiskViewData::from($base);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertGates(array $data): void
    {
        $response = trim((string) ($data['response'] ?? ''));
        $treatment = trim((string) ($data['treatment'] ?? ''));

        if ($response === RiskResponse::ACCEPT && $treatment === '') {
            throw ValidationException::withMessages([
                'treatment' => __('ui.risk_accept_rationale_required'),
            ]);
        }
    }
}
