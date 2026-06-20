<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
        ['name' => 'Electronics',  'slug' => 'electronics'],
        ['name' => 'Books',        'slug' => 'books'],
        ['name' => 'Clothing',     'slug' => 'clothing'],
        ['name' => 'Furniture',    'slug' => 'furniture'],
        ['name' => 'Stationery',   'slug' => 'stationery'],
        ['name' => 'Other',        'slug' => 'other'],
    ];

    foreach ($categories as $category) {
        Category::create($category);
    }
    }
}
