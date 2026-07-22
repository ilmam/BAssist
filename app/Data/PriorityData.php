<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class PriorityData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $name = '',
        #[ListForm('text')]
        public string $code = '',
        #[Form('number')]
        public int $sort_order = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
    ) {
    }

    public static function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
