<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class TenantData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $name = '',
        #[Form('text')]
        public string $slug = '',
        #[ListForm('select', 'Status')]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
