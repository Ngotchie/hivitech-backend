<?php

namespace Database\Seeders;

use App\Models\Technology;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TechnologySeeder extends Seeder
{
    public function run(): void
    {
        $technologies = [
            'Laravel', 'PHP', 'PostgreSQL', 'MySQL', 'Vue.js', 'Nuxt', 'JavaScript', 'TypeScript',
            'Flutter', 'Dart', 'WordPress', 'WooCommerce', 'Docker', 'Redis', 'Meilisearch',
            'MinIO', 'REST API', 'Git',
        ];

        foreach ($technologies as $index => $name) {
            Technology::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true],
            );
        }
    }
}
