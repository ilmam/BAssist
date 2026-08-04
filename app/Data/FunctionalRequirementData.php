<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class FunctionalRequirementData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('text', readonly: true)]
        public ?string $code = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project')]
        public int $project_id = 0,

        #[Form('select', 'StakeholderNeed', help: 'Links this functional requirement in the project traceability matrix.')]
        public ?int $stakeholder_need_id = null,

        #[Form('textarea', hideQuick: true)]
        public string $statement = '',

        #[Form('textarea', hideQuick: true)]
        public ?string $trigger = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $acceptance_criteria = null,

        #[Form('select', 'Priority')]
        public ?int $priority_id = null,

        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'stakeholder_need_id' => ['nullable', 'integer', 'exists:stakeholder_needs,id'],
            'statement' => ['required', 'string'],
            'trigger' => ['nullable', 'string'],
            'acceptance_criteria' => ['nullable', 'string'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
