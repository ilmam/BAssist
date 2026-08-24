<?php

namespace App\Repositories;

use App\Data\DataDictionaryData;
use App\Data\DataDictionaryViewData;
use App\Models\DataDictionary;
use App\Services\DataDictionaryNormalizer;
use Illuminate\Database\Eloquent\Model;

class DataDictionaryRepository extends BaseRepository
{
    public Model $model;

    public $editDto = DataDictionaryData::class;

    public $viewDto = DataDictionaryViewData::class;

    protected array $listFilters = [
        'project_id',
        'status_id',
    ];

    protected array $listContextFilters = [
        'workspace_id' => ['project', 'workspace_id'],
    ];

    protected string|array|null $listTenantScope = ['project.workspace', 'tenant_id'];

    protected array $listContextRelations = [
        'project.workspace',
    ];

    public function __construct(
        protected DataDictionaryNormalizer $normalizer = new DataDictionaryNormalizer,
    ) {
        $this->model = new DataDictionary;
    }

    public function create(array $data)
    {
        $data = $this->normalizeEntitiesPayload($data);

        return $this->model::create($this->filterFillable($data));
    }

    public function update($id, array $newData)
    {
        $newData = $this->normalizeEntitiesPayload($newData);

        /** @var DataDictionary $dictionary */
        $dictionary = $this->model::findOrFail($id);
        $dictionary->update($this->filterFillable($newData));

        return $dictionary->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeEntitiesPayload(array $data): array
    {
        if (! array_key_exists('entities', $data)) {
            return $data;
        }

        $data['entities'] = $this->normalizer->normalize($data['entities']);

        return $data;
    }
}
