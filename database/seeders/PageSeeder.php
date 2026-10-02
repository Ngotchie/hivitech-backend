<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['À propos', 'Découvrez Hivitech, ses domaines d’expertise et son engagement dans l’accompagnement numérique des organisations.', PageStatus::Published],
            ['Mentions légales', 'Contenu juridique provisoire à compléter et à faire valider avec les informations légales officielles de Hivitech.', PageStatus::Draft],
            ['Politique de confidentialité', 'Politique provisoire à compléter et à faire valider avant publication, notamment concernant les traitements de données personnelles.', PageStatus::Draft],
            ['Conditions générales', 'Conditions générales provisoires à compléter et à faire valider avant toute publication.', PageStatus::Draft],
        ];
        foreach ($pages as $index => [$title, $content, $status]) {
            Page::query()->updateOrCreate(['slug' => Str::slug($title)], ['title' => $title, 'content' => $content, 'status' => $status,
                'published_at' => $status === PageStatus::Published ? now() : null, 'seo_title' => $title, 'sort_order' => $index + 1]);
        }
    }
}
