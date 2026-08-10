<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;
use App\Support\NfrCategory;

class NonFunctionalRequirementViewData extends BaseData
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
        public int $project_id = 0,
        #[Hide]
        public ?int $workspace_id = null,
        #[Hide]
        public ?int $tenant_id = null,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?int $stakeholder_need_id = null,
        #[InList]
        public ?StakeholderNeedViewData $stakeholder_need = null,
        public ?int $change_request_id = null,
        #[InList]
        public ?ChangeRequestViewData $change_request = null,
        #[InList]
        public string $category = '',
        public string $description = '',
        public ?string $acceptance_criteria = null,
        public ?int $priority_id = null,
        #[InList]
        public ?PriorityViewData $priority = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
    ) {
    }

    public function getFields($onlyHeaders = false, $withPrefix = true, $prefix = '', $object = null)
    {
        $fields = parent::getFields($onlyHeaders, $withPrefix, $prefix, $object);

        if (! $onlyHeaders && isset($fields['category']) && filled($fields['category'])) {
            $fields['category'] = NfrCategory::label((string) $fields['category']);
        }

        return $fields;
    }
}
