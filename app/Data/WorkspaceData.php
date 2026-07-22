<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Attributes\Value;

class WorkspaceData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        #[Value]
        public string $name = '',
        #[Form('text')]
        #[Value]
        public string $slug = '',
        #[Form('select', 'Tenant')]
        #[Value]
        public int $tenant_id = 0,
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
            'slug' => ['required', 'string', 'max:255'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
