<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class ScreenViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $title = '',
        public int $project_id = 0,
        #[Hide]
        public ?int $workspace_id = null,
        #[Hide]
        public ?int $tenant_id = null,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?string $description = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
        /** Codes of the functional requirements this screen realizes (upstream). */
        public ?string $realizes = null,
        /** The mockup as PlantUML Salt, assembled from the element rows. */
        public ?string $salt = null,
    ) {
    }
}
