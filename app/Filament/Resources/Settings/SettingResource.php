<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\Pages\CreateSetting;
use App\Filament\Resources\Settings\Pages\EditSetting;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Paramètres du site';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->label('Clé')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('label')->label('Libellé')->maxLength(255),
            Select::make('group')->label('Groupe')->options(['company' => 'Entreprise', 'social' => 'Réseaux sociaux', 'branding' => 'Identité visuelle', 'general' => 'Général']),
            Select::make('type')->options(['text' => 'Texte', 'email' => 'E-mail', 'phone' => 'Téléphone', 'url' => 'URL', 'image' => 'Image', 'textarea' => 'Texte long'])->default('text')->required()->live(),
            TextInput::make('value')->label('Valeur')->visible(fn (Get $get) => ! in_array($get('type'), ['image', 'textarea']))->email(fn (Get $get) => $get('type') === 'email')->url(fn (Get $get) => $get('type') === 'url'),
            Textarea::make('value')->label('Valeur')->visible(fn (Get $get) => $get('type') === 'textarea'),
            FileUpload::make('value')->label('Image')->disk('s3')->directory('settings')->image()->visible(fn (Get $get) => $get('type') === 'image'),
            TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->required(),
            Toggle::make('is_public')->label('Public')->default(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label')->label('Libellé')->searchable(), TextColumn::make('key')->label('Clé')->searchable(),
            TextColumn::make('group')->label('Groupe')->badge(), TextColumn::make('type')->label('Type'),
            IconColumn::make('is_public')->label('Public')->boolean(), TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i'),
        ])->filters([SelectFilter::make('group')->options(['company' => 'Entreprise', 'social' => 'Réseaux sociaux', 'branding' => 'Identité visuelle', 'general' => 'Général']), TernaryFilter::make('is_public')->label('Public')->boolean()])
            ->recordActions([EditAction::make(), DeleteAction::make()])->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSettings::route('/'), 'create' => CreateSetting::route('/create'), 'edit' => EditSetting::route('/{record}/edit')];
    }
}
