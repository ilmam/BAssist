<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class BriefViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $title = '',
        public int $workspace_id = 0,
        #[InList]
        public ?WorkspaceViewData $workspace = null,
        public ?string $ask = null,
        public ?string $source_type = null,
        public ?string $source_note = null,
        public ?string $stated_constraints = null,
        public ?string $stated_success_measures = null,
        public ?string $open_questions = null,
        #[InList]
        public string $status = '',
        public ?string $status_reason = null,
        public ?string $received_at = null,
        public int $version = 1,
    ) {
    }
}
