<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['company_name', 'Hivitech', 'company', 'text', 'Nom de l’entreprise'],
            ['company_tagline', 'Votre partenaire en transformation numérique', 'company', 'text', 'Slogan'],
            ['email', null, 'company', 'email', 'E-mail'], ['phone', null, 'company', 'phone', 'Téléphone'],
            ['whatsapp', null, 'company', 'phone', 'WhatsApp'], ['address', null, 'company', 'textarea', 'Adresse'],
            ['city', 'Yaoundé', 'company', 'text', 'Ville'], ['country', 'Cameroun', 'company', 'text', 'Pays'],
            ['facebook_url', null, 'social', 'url', 'Facebook'], ['linkedin_url', null, 'social', 'url', 'LinkedIn'],
            ['instagram_url', null, 'social', 'url', 'Instagram'], ['youtube_url', null, 'social', 'url', 'YouTube'],
            ['logo_path', null, 'branding', 'image', 'Logo'], ['favicon_path', null, 'branding', 'image', 'Favicon'],
        ];
        foreach ($settings as $index => [$key, $value, $group, $type, $label]) {
            Setting::query()->updateOrCreate(['key' => $key], compact('value', 'group', 'type', 'label') + ['sort_order' => $index + 1, 'is_public' => true]);
        }
    }
}
