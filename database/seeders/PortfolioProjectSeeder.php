<?php

namespace Database\Seeders;

use App\Models\PortfolioProject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PortfolioProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = ['BCC', 'ACP Syneco', 'Chic Aparts', 'Wafrica TV', 'Mayem Solutions', 'Mouansiecam'];

        foreach ($projects as $index => $title) {
            PortfolioProject::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'client_name' => $title,
                    'short_description' => 'Conception et mise en œuvre d’une solution digitale adaptée aux besoins du client.',
                    'description' => 'Présentation du projet à compléter après validation des informations métier et des éléments de communication.',
                    'is_featured' => false,
                    'is_published' => false,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
