<?php

namespace App\Filament\Resources\PortfolioProjects;

use App\Filament\Resources\PortfolioProjects\Pages\CreatePortfolioProject;
use App\Filament\Resources\PortfolioProjects\Pages\EditPortfolioProject;
use App\Filament\Resources\PortfolioProjects\Pages\ListPortfolioProjects;
use App\Filament\Resources\PortfolioProjects\RelationManagers\PortfolioProjectImagesRelationManager;
use App\Models\PortfolioProject;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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

class PortfolioProjectResource extends Resource
{
    protected static ?string $model = PortfolioProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Portfolio';

    protected static ?string $navigationLabel = 'Réalisations';

    protected static ?string $modelLabel = 'réalisation';

    protected static ?string $pluralModelLabel = 'réalisations';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations générales')->schema([
                TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('client_name')->label('Client')->maxLength(255),
                TextInput::make('website_url')->label('Site web')->url()->maxLength(255),
                FileUpload::make('client_logo_path')->label('Logo du client')->disk('s3')->directory('portfolio/logos')->image(),
                FileUpload::make('cover_image_path')->label('Image de couverture')->disk('s3')->directory('portfolio/covers')->image(),
                Textarea::make('short_description')->label('Description courte')->columnSpanFull(),
                Textarea::make('description')->label('Description')->rows(8)->columnSpanFull(),
                DatePicker::make('completed_at')->label('Date de réalisation'),
            ])->columns(2)->columnSpanFull(),
            Section::make('Étude de cas')->schema([
                Textarea::make('challenge')->label('Problématique / Besoin')->rows(6),
                Textarea::make('solution')->label('Solution apportée')->rows(6),
                Textarea::make('results')->label('Résultats')->rows(6),
            ])->columns(3)->columnSpanFull(),
            Section::make('Services & Technologies')->schema([
                Select::make('technologies')
                    ->relationship('technologies', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Select::make('services')
                    ->relationship('services', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Publication')->schema([
                Toggle::make('is_published')->label('Publiée')->default(false),
                Toggle::make('is_featured')->label('Mise en avant')->default(false),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->required(),
            ])->columns(3)->columnSpanFull(),
            Section::make('SEO')
                ->description('Ces informations optimisent l’affichage de la réalisation dans les moteurs de recherche.')
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
                TextColumn::make('title')->label('Titre')->searchable(['title', 'slug', 'client_name', 'short_description'])->sortable(),
                TextColumn::make('client_name')->label('Client')->sortable()->toggleable(),
                TextColumn::make('technologies.name')->label('Technologies')->badge()->limitList(3),
                TextColumn::make('services_count')->label('Services')->counts('services')->sortable(),
                TextColumn::make('completed_at')->label('Réalisée le')->date('d/m/Y')->sortable()->placeholder('—'),
                IconColumn::make('is_featured')->label('Mise en avant')->boolean(),
                IconColumn::make('is_published')->label('Publiée')->boolean(),
                TextColumn::make('sort_order')->label('Ordre')->sortable(),
                TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Publiée')->boolean(),
                TernaryFilter::make('is_featured')->label('Mise en avant')->boolean(),
                SelectFilter::make('technologies')->label('Technologie')->relationship('technologies', 'name')->searchable()->preload(),
                SelectFilter::make('services')->label('Service')->relationship('services', 'name')->searchable()->preload(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [PortfolioProjectImagesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPortfolioProjects::route('/'),
            'create' => CreatePortfolioProject::route('/create'),
            'edit' => EditPortfolioProject::route('/{record}/edit'),
        ];
    }
}
