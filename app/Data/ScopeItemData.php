<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\ScopeItemDirection;
use Illuminate\Validation\Rule;

class ScopeItemData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project')]
        public int $project_id = 0,

        #[ListForm('select', 'ScopeItemDirection')]
        public string $direction = ScopeItemDirection::IN,

        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'direction' => ['required', 'string', Rule::in(ScopeItemDirection::values())],
            'description' => ['nullable', 'string'],
        ];
    }
}
