<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Attributes\Value;

class StatusData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        #[Value]
        public string $name = '',
        #[ListForm('text')]
        #[Value]
        public string $code = '',
        #[Form('number')]
        #[Value]
        public int $sort_order = 0,
        #[Form('textarea', hideQuick: true)]
        #[Value]
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
