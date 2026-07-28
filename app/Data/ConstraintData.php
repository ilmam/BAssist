<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\ConstraintStatus;
use Illuminate\Validation\Rule;

class ConstraintData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $title = '',
        #[Form('select', 'Project')]
        public int $project_id = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
        #[ListForm('select', 'ConstraintStatus')]
        public string $status = ConstraintStatus::ACTIVE,
        #[Form('text', hideQuick: true)]
        public ?string $source = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(ConstraintStatus::values())],
            'source' => ['nullable', 'string', 'max:255'],
        ];
    }
}
