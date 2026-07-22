<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Attributes\Value;

class ProjectData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        #[Value]
        public string $name = '',
        #[Form('text')]
        #[Value]
        public ?string $code = null,
        #[Form('select', 'Workspace')]
        #[Value]
        public int $workspace_id = 0,
        #[Form('textarea', hideQuick: true)]
        #[Value]
        public ?string $description = null,
        #[ListForm('select', 'Status', hideQuick: true)]
        #[Value]
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
