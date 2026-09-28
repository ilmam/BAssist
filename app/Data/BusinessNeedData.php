<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class BusinessNeedData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('text', readonly: true)]
        public ?string $code = null,

        #[Form('radio', 'NeedType')]
        public ?string $need_type = null,

        #[Form('text', uiSpan: 12)]
        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $rationale = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $impact = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $do_nothing_consequence = null,

        #[Form('attachments', hideQuick: true)]
        public mixed $attachments = null,
    ) {
    }
}
