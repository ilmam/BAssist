<?php

namespace App\Data;

use App\Attributes\FormFieldAttribute;
use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class TenantData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public string $name = '',
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public string $slug = '',
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'Status')]
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
