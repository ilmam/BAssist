<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;
use App\Support\NeedType;

class BusinessNeedViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[Hide]
        public ?int $number = null,
        #[InList]
        public ?string $code = null,
        #[InList]
        public string $title = '',
        public ?string $need_type = null,
        public int $project_id = 0,
        #[Hide]
        public ?int $workspace_id = null,
        #[Hide]
        public ?int $tenant_id = null,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?string $description = null,
        public ?string $rationale = null,
        public ?string $impact = null,
        public ?string $do_nothing_consequence = null,
        #[Hide]
        public ?int $business_objectives_count = null,
        #[Hide]
        public bool $is_orphan = false,
    ) {
    }

    public function getFields($onlyHeaders = false, $withPrefix = true, $prefix = '', $object = null)
    {
        $fields = parent::getFields($onlyHeaders, $withPrefix, $prefix, $object);

        if (! $onlyHeaders && isset($fields['need_type']) && filled($fields['need_type'])) {
            $fields['need_type'] = NeedType::label((string) $fields['need_type']);
        }

        return $fields;
    }
}
