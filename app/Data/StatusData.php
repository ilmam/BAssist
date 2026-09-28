<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use Spatie\LaravelData\Attributes\Validation\Min;

class StatusData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $name = '',
        #[ListForm('text')]
        public string $code = '',
        #[Form('number'), Min(0)]
        public int $sort_order = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
    ) {
    }
}
