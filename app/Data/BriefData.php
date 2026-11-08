<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\BriefStatus;

class BriefData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $title = '',
        #[Form('select', 'Workspace', hideQuick: true)]
        public int $workspace_id = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $ask = null,
        #[Form('select', 'BriefSourceType')]
        public ?string $source_type = null,
        #[Form('text', hideQuick: true)]
        public ?string $source_note = null,
        #[Form('textarea', hideQuick: true)]
        public ?string $stated_constraints = null,
        #[Form('textarea', hideQuick: true)]
        public ?string $stated_success_measures = null,
        #[Form('textarea', hideQuick: true)]
        public ?string $open_questions = null,
        #[ListForm('select', 'BriefStatus')]
        public string $status = BriefStatus::RECEIVED,
        #[Form('textarea', hideQuick: true)]
        public ?string $status_reason = null,
        #[Form('date', hideQuick: true)]
        public ?string $received_at = null,

        #[Form('attachments', hideQuick: true)]
        public mixed $attachments = null,
    ) {
    }
}
