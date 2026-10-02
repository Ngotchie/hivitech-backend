<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $modelLabel = 'service';

    protected static ?string $pluralModelLabel = 'services';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations générales')
                ->schema([
                    Select::make('service_category_id')
                        ->label('Catégorie')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                            if ($operation === 'create') {
                                $set('slug', Str::slug($state ?? ''));
                            }
                        }),
                    TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                    Textarea::make('short_description')->label('Description courte')->columnSpanFull(),
                    Textarea::make('description')->rows(8)->columnSpanFull(),
                    TextInput::make('icon')->label('Icône')->maxLength(255),
                    TextInput::make('image_path')->label('Chemin de l’image')->maxLength(255),
                ])->columns(2)->columnSpanFull(),
            Section::make('Tarification')
                ->schema([
                    TextInput::make('starting_price')
                        ->label('Prix à partir de')
                        ->numeric()
                        ->suffix('FCFA'),
                    Toggle::make('price_on_request')->label('Prix sur demande')->default(false),
                    TextInput::make('duration_text')->label('Durée indicative')->maxLength(255),
                ])->columns(3)->columnSpanFull(),
            Section::make('Publication')
                ->schema([
                    Toggle::make('is_active')->label('Actif')->default(true),
                    Toggle::make('is_featured')->label('Mis en avant')->default(false),
                    TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->required(),
                ])->columns(3)->columnSpanFull(),
            Section::make('SEO')
                ->description('Ces informations sont utilisées pour optimiser le référencement du service.')
                ->schema([
                    TextInput::make('seo_title')->label('Titre SEO')->maxLength(255),
                    Textarea::make('seo_description')->label('Description SEO')->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(['name', 'slug', 'short_description'])->sortable(),
                TextColumn::make('category.name')->label('Catégorie')->searchable()->sortable(),
                TextColumn::make('starting_price')->label('Prix initial')->money('XAF')->sortable()->placeholder('—'),
                IconColumn::make('price_on_request')->label('Sur demande')->boolean(),
                IconColumn::make('is_featured')->label('Mis en avant')->boolean(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('sort_order')->label('Ordre')->sortable(),
                TextColumn::make('updated_at')->label('Mis à jour')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('service_category_id')->label('Catégorie')->relationship('category', 'name')->searchable()->preload(),
                TernaryFilter::make('is_active')->label('Actif')->boolean(),
                TernaryFilter::make('is_featured')->label('Mis en avant')->boolean(),
                TernaryFilter::make('price_on_request')->label('Prix sur demande')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
