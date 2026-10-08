<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Models\FunctionalRequirement;
use App\Rules\RecordExists;
use App\Support\ScreenElementKind;
use Illuminate\Validation\Rule;

class ScreenData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('select', 'FunctionalRequirement', help: 'Upstream link: a functional requirement this screen realizes. Saving adds it; further links are added the same way.', hideQuick: true, section: 'traceability')]
        public ?int $functional_requirement_id = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,

        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,

        /**
         * The element rows, in order. null leaves the stored rows alone; an array
         * (even empty) replaces them. See ScreenRepository::syncElements().
         *
         * @var list<array{id?: int|null, key?: string|null, parent_key?: string|null, row?: int|null, kind?: string, label?: string, functional_requirement_id?: int|null}>|null
         */
        public ?array $elements = null,
    ) {
    }

    public static function rules()
    {
        return [
            'elements' => ['nullable', 'array'],
            'elements.*.id' => ['nullable', 'integer', 'min:1'],
            'elements.*.key' => ['nullable', 'string', 'max:64'],
            'elements.*.parent_key' => ['nullable', 'string', 'max:64'],
            'elements.*.row' => ['nullable', 'integer', 'min:0'],
            'elements.*.kind' => ['nullable', 'string', Rule::in(['', ...ScreenElementKind::values()])],
            'elements.*.label' => ['nullable', 'string', 'max:255'],
            'elements.*.functional_requirement_id' => ['nullable', 'integer', new RecordExists(FunctionalRequirement::class)],
        ];
    }
}
