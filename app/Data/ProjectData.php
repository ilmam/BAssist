<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class ProjectData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $name = '',
        #[Form('text')]
        public ?string $code = null,
        #[Form('select', 'Workspace')]
        public int $workspace_id = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
