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
            ['code' => EntityStatus::DRAFT, 'name' => 'Draft', 'sort_order' => 10, 'description' => 'Work in progress; not yet agreed.'],
            ['code' => EntityStatus::AGREED, 'name' => 'Agreed', 'sort_order' => 20, 'description' => 'Accepted and active in the spine.'],
            ['code' => EntityStatus::DEPRECATED, 'name' => 'Deprecated', 'sort_order' => 30, 'description' => 'Superseded or no longer in use.'],
        ];

        foreach ($statuses as $status) {
            Status::query()->updateOrCreate(
                ['code' => $status['code']],
                $status,
            );
        }

        $priorities = [
            ['code' => EntityPriority::HIGH, 'name' => 'High', 'sort_order' => 10, 'description' => 'Must be addressed soon.'],
            ['code' => EntityPriority::MEDIUM, 'name' => 'Medium', 'sort_order' => 20, 'description' => 'Important but not urgent.'],
            ['code' => EntityPriority::LOW, 'name' => 'Low', 'sort_order' => 30, 'description' => 'Can wait relative to other work.'],
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
