<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Développement', 'Conception de solutions web, mobiles et métier adaptées aux objectifs de chaque organisation.'],
            ['Hébergement', 'Solutions fiables pour héberger, sécuriser et maintenir vos services numériques disponibles.'],
            ['Réseaux & Infrastructures', 'Conception, déploiement et optimisation d’infrastructures réseau performantes et évolutives.'],
            ['Cybersécurité', 'Protection des systèmes, des données et des utilisateurs face aux risques numériques.'],
            ['Maintenance informatique', 'Assistance et maintenance pour garantir la continuité de votre environnement informatique.'],
            ['Formations', 'Formations pratiques pour développer durablement les compétences numériques des équipes.'],
        ];

        foreach ($categories as $index => [$name, $description]) {
            ServiceCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $description, 'sort_order' => $index + 1, 'is_active' => true],
            );
        }
    }
}
