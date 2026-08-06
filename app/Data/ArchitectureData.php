<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\Hide;
use App\Attributes\ListForm;
use Illuminate\Validation\Rule;

class ArchitectureData extends BaseData
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
        public array $elements = [],

        /** @var list<array<string, mixed>> */
        #[Hide]
        public array $relationships = [],

        /** @var array{shapes_per_row?: int, boundaries_per_row?: int} */
        #[Hide]
        public array $layout = [],

        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'description' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
            'elements' => ['nullable', 'array'],
            'elements.*.key' => ['nullable', 'string', 'max:64'],
            'elements.*.kind' => ['nullable', 'string', Rule::in(['person', 'system', 'container', 'component', 'group', ''])],
            'elements.*.name' => ['nullable', 'string', 'max:255'],
            'elements.*.description' => ['nullable', 'string', 'max:2000'],
            'elements.*.technology' => ['nullable', 'string', 'max:255'],
            'elements.*.parent_key' => ['nullable', 'string', 'max:64'],
            'elements.*.external' => ['nullable'],
            'elements.*.form' => ['nullable', 'string', Rule::in(['box', 'database', 'queue'])],
            'elements.*.feature_ids' => ['nullable', 'array'],
            'elements.*.feature_ids.*' => ['integer', 'exists:features,id'],
            'elements.*.bg_color' => ['nullable', 'string', 'max:32'],
            'elements.*.font_color' => ['nullable', 'string', 'max:32'],
            'elements.*.border_color' => ['nullable', 'string', 'max:32'],
            'elements.*.style' => ['nullable', 'array'],
            'relationships' => ['nullable', 'array'],
            'relationships.*.from_key' => ['nullable', 'string', 'max:64'],
            'relationships.*.to_key' => ['nullable', 'string', 'max:64'],
            'relationships.*.label' => ['nullable', 'string', 'max:255'],
            'relationships.*.technology' => ['nullable', 'string', 'max:255'],
            'relationships.*.direction' => ['nullable', 'string', Rule::in(['', 'rel', 'up', 'down', 'left', 'right', 'back', 'bi'])],
            'relationships.*.line_color' => ['nullable', 'string', 'max:32'],
            'layout' => ['nullable', 'array'],
            'layout.shapes_per_row' => ['nullable', 'integer', 'min:1', 'max:12'],
            'layout.boundaries_per_row' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
