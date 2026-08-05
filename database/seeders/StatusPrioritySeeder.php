<?php

namespace Database\Seeders;

use App\Models\Priority;
use App\Models\Status;
use App\Support\EntityPriority;
use App\Support\EntityStatus;
use Illuminate\Database\Seeder;

class StatusPrioritySeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => EntityStatus::DRAFT, 'name' => 'Draft', 'sort_order' => 10, 'description' => 'Work in progress; not yet agreed.', 'is_system' => true],
            ['code' => EntityStatus::AGREED, 'name' => 'Agreed', 'sort_order' => 20, 'description' => 'Accepted for the current baseline.', 'is_system' => true],
            ['code' => EntityStatus::NEED_REVISION, 'name' => 'Need Revision', 'sort_order' => 25, 'description' => 'Flagged by an approved change request; must be kept, revised (via new item), or deprecated.', 'is_system' => true],
            ['code' => EntityStatus::DEPRECATED, 'name' => 'Deprecated', 'sort_order' => 30, 'description' => 'Superseded or no longer in scope.', 'is_system' => true],
        ];

        foreach ($statuses as $status) {
            Status::query()->updateOrCreate(
                ['code' => $status['code']],
                $status,
            );
        }

        $priorities = [
            ['code' => EntityPriority::MUST, 'name' => 'Must', 'sort_order' => 10, 'description' => 'Without this, the release fails.', 'is_system' => true],
            ['code' => EntityPriority::SHOULD, 'name' => 'Should', 'sort_order' => 20, 'description' => 'Important, but a workaround exists.', 'is_system' => true],
            ['code' => EntityPriority::COULD, 'name' => 'Could', 'sort_order' => 30, 'description' => 'Nice to have if capacity remains.', 'is_system' => true],
            ['code' => EntityPriority::WONT, 'name' => "Won't", 'sort_order' => 40, 'description' => 'Explicitly out of scope for this release.', 'is_system' => true],
        ];

        foreach ($priorities as $priority) {
            Priority::query()->updateOrCreate(
                ['code' => $priority['code']],
                $priority,
            );
        }

        EntityStatus::forgetCache();
        EntityPriority::forgetCache();
    }
}
