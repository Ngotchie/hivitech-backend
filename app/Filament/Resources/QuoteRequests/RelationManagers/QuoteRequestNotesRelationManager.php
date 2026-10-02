<?php

namespace App\Filament\Resources\QuoteRequests\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuoteRequestNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Historique des notes internes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('content')->label('Note')->required()->rows(5)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Auteur')->placeholder('Utilisateur supprimé'),
                TextColumn::make('content')->label('Contenu')->wrap(),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'user_id' => auth()->id()]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
