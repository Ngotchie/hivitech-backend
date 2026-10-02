<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->first();
        $posts = [
            ['Pourquoi votre entreprise a besoin d’un site internet professionnel', 'Développement', PostStatus::Published, true],
            ['Comment choisir un hébergement web adapté à son activité', 'Hébergement', PostStatus::Published, false],
            ['Comment protéger efficacement son ordinateur', 'Cybersécurité', PostStatus::Published, true],
            ['WordPress ou développement sur mesure : que choisir ?', 'Développement', PostStatus::Draft, false],
            ['Comment l’intelligence artificielle peut aider une PME', 'Intelligence artificielle', PostStatus::Review, false],
            ['Pourquoi former son personnel à Excel', 'Formations', PostStatus::Draft, false],
            ['Comment choisir un routeur Wi-Fi pour son entreprise', 'Réseaux & Infrastructures', PostStatus::Published, false],
            ['Les bonnes pratiques pour sécuriser ses mots de passe', 'Conseils pratiques', PostStatus::Published, false],
        ];

        foreach ($posts as $index => [$title, $categoryName, $status, $featured]) {
            $category = PostCategory::query()->where('name', $categoryName)->first();
            Post::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'post_category_id' => $category?->getKey(),
                    'author_id' => $author?->getKey(),
                    'title' => $title,
                    'excerpt' => 'Un guide introductif pour mieux comprendre le sujet et faire des choix adaptés à votre activité.',
                    'content' => "Cet article présente les principaux éléments à connaître sur le thème « {$title} ». Il propose une approche claire et pratique afin d’aider les professionnels à identifier leurs besoins et à préparer leurs décisions.\n\nLe contenu pourra être enrichi par l’équipe Hivitech avant sa diffusion définitive.",
                    'status' => $status,
                    'is_featured' => $featured,
                    'published_at' => $status === PostStatus::Published ? now()->subDays(8 - $index) : null,
                    'seo_title' => $title,
                    'seo_description' => 'Découvrez les conseils Hivitech pour mieux comprendre ce sujet et préparer votre projet numérique.',
                ],
            );
        }
    }
}
