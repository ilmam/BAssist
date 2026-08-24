<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\Hide;
use App\Attributes\ListForm;
use App\Services\DataTypeInference;

class DataDictionaryData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,

        /** @var list<array<string, mixed>> */
        #[Hide]
        public array $entities = [],

        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {}

    public static function rules()
    {
        $types = implode(',', array_merge(['', 'auto'], DataTypeInference::TYPES));

        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'description' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
            'entities' => ['nullable', 'array'],
            'entities.*.name' => ['nullable', 'string', 'max:255'],
            'entities.*.meaning' => ['nullable', 'string', 'max:2000'],
            'entities.*.fields' => ['nullable', 'array'],
            'entities.*.fields.*.name' => ['nullable', 'string', 'max:255'],
            'entities.*.fields.*.meaning' => ['nullable', 'string', 'max:2000'],
            'entities.*.fields.*.type' => ['nullable', 'string', 'in:'.$types],
            'entities.*.fields.*.is_pk' => ['nullable'],
            'entities.*.fields.*.references' => ['nullable', 'string', 'max:255'],
            'entities.*.fields.*.business_may_set' => ['nullable'],
            'entities.*.fields.*.business_may_see' => ['nullable'],
        ];
    }
}
