<?php

namespace App\Filament\Resources\Technologies;

use App\Filament\Resources\Technologies\Pages\CreateTechnology;
use App\Filament\Resources\Technologies\Pages\EditTechnology;
use App\Filament\Resources\Technologies\Pages\ListTechnologies;
use App\Models\Technology;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class TechnologyResource extends Resource
{
    protected static ?string $model = Technology::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static string|UnitEnum|null $navigationGroup = 'Portfolio';

    protected static ?string $navigationLabel = 'Technologies';

    protected static ?string $modelLabel = 'technologie';

    protected static ?string $pluralModelLabel = 'technologies';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
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
            FileUpload::make('logo_path')->label('Logo')->disk('s3')->directory('technologies/logos')->image(),
            TextInput::make('website_url')->label('Site web')->url()->maxLength(255),
            TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->required(),
            Toggle::make('is_active')->label('Active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable()->toggleable(),
                TextColumn::make('portfolio_projects_count')->label('Réalisations')->counts('portfolioProjects')->sortable(),
                TextColumn::make('website_url')->label('Site web')->url(fn (Technology $record): ?string => $record->website_url)->openUrlInNewTab()->toggleable(),
                TextColumn::make('sort_order')->label('Ordre')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Statut actif')->boolean()])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Technology $record): bool => $record->portfolioProjects()->exists())
                    ->tooltip(fn (Technology $record): ?string => $record->portfolioProjects()->exists()
                        ? 'Cette technologie est associée à des réalisations.'
                        : null),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTechnologies::route('/'),
            'create' => CreateTechnology::route('/create'),
            'edit' => EditTechnology::route('/{record}/edit'),
        ];
    }
}
