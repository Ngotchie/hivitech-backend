<?php

namespace App\Filament\Resources\PortfolioProjects\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortfolioProjectImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Galerie d’images';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('Image')
                ->disk('s3')
                ->directory('portfolio/gallery')
                ->image()
                ->required()
                ->columnSpanFull(),
            TextInput::make('alt_text')->label('Texte alternatif')->maxLength(255),
            TextInput::make('caption')->label('Légende')->maxLength(255),
            TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->required(),
            Toggle::make('is_cover')->label('Image de couverture')->default(false),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alt_text')
            ->columns([
                TextColumn::make('path')->label('Fichier')->limit(40),
                TextColumn::make('alt_text')->label('Texte alternatif')->searchable(),
                TextColumn::make('caption')->label('Légende')->toggleable(),
                TextColumn::make('sort_order')->label('Ordre')->sortable(),
                IconColumn::make('is_cover')->label('Couverture')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }
}
