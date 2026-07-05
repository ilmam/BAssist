<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category' => 'Electronics', 'description' => 'Electronic devices and accessories'],
            ['category' => 'Furniture', 'description' => 'Office and home furniture'],
            ['category' => 'Clothing', 'description' => 'Apparel and fashion items'],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(
                ['category' => $category['category']],
                ['description' => $category['description']]
            );
        }
    }
}
