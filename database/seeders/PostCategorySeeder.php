<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Développement', 'Conseils et ressources sur le développement de solutions numériques.'],
            ['Cybersécurité', 'Bonnes pratiques pour protéger les systèmes, les données et les utilisateurs.'],
            ['Intelligence artificielle', 'Découverte des usages concrets et responsables de l’intelligence artificielle.'],
            ['Réseaux & Infrastructures', 'Informations pratiques sur les réseaux et infrastructures informatiques.'],
            ['Hébergement', 'Guides pour choisir et administrer des solutions d’hébergement adaptées.'],
            ['Logiciels', 'Présentation et utilisation efficace des logiciels professionnels.'],
            ['Matériel informatique', 'Conseils pour choisir, entretenir et optimiser les équipements informatiques.'],
            ['Conseils pratiques', 'Recommandations simples pour mieux utiliser les outils numériques au quotidien.'],
            ['Formations', 'Ressources pour développer les compétences numériques des professionnels.'],
            ['Actualités Hivitech', 'Informations et nouveautés concernant les activités de Hivitech.'],
        ];

        foreach ($categories as $index => [$name, $description]) {
            PostCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $description, 'sort_order' => $index + 1, 'is_active' => true],
            );
        }
    }
}
