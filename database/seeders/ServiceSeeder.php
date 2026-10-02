<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'Développement' => ['Création de sites internet', 'Applications web', 'Applications mobiles', 'Applications métier', 'ERP & CRM', 'Plateformes SaaS', 'API & intégrations', 'Maintenance applicative'],
            'Hébergement' => ['Hébergement web', 'Nom de domaine', 'E-mail professionnel', 'Serveur VPS', 'Migration de site', 'Sauvegarde & supervision'],
            'Réseaux & Infrastructures' => ['Installation réseau', 'Câblage informatique', 'Installation Wi-Fi', 'Configuration de routeurs', 'Installation de serveurs', 'Téléphonie IP', 'Installation Starlink', 'Audit réseau'],
            'Cybersécurité' => ['Audit de sécurité', 'Protection des postes', 'Gestion des accès', 'Sauvegarde sécurisée', 'Sécurisation des e-mails', 'Sensibilisation à la cybersécurité'],
            'Maintenance informatique' => ['Maintenance préventive', 'Maintenance corrective', 'Dépannage informatique', 'Installation de logiciels', 'Optimisation des ordinateurs', 'Assistance à distance', 'Contrat de maintenance'],
            'Formations' => ['Initiation à l’informatique', 'Microsoft Word', 'Microsoft Excel', 'Microsoft PowerPoint', 'WordPress', 'Développement web', 'Programmation', 'Arduino', 'Intelligence artificielle', 'Cybersécurité'],
        ];

        foreach ($services as $categoryName => $categoryServices) {
            $category = ServiceCategory::query()->where('name', $categoryName)->first();

            if (! $category) {
                throw new RuntimeException("La catégorie [{$categoryName}] doit être créée avant ses services.");
            }

            foreach ($categoryServices as $index => $name) {
                Service::query()->updateOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'service_category_id' => $category->getKey(),
                        'name' => $name,
                        'short_description' => "Un service professionnel de {$name} adapté à vos besoins et à votre environnement.",
                        'description' => "Hivitech vous accompagne pour votre projet de {$name}, de l’analyse du besoin jusqu’à la mise en œuvre et au suivi.",
                        'price_on_request' => true,
                        'starting_price' => null,
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ],
                );
            }
        }
    }
}
