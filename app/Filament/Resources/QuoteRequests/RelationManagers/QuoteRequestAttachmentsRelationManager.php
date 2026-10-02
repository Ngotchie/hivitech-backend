<?php

namespace App\Filament\Resources\QuoteRequests\RelationManagers;

use App\Models\QuoteRequestAttachment;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class QuoteRequestAttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Pièces jointes';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')->label('Fichier')->searchable(),
                TextColumn::make('mime_type')->label('Type'),
                TextColumn::make('size')->label('Taille')->formatStateUsing(fn (?int $state): string => $state === null ? '—' : number_format($state / 1024, 1, ',', ' ').' Ko'),
                TextColumn::make('created_at')->label('Ajoutée le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                Action::make('download')->label('Télécharger')->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (QuoteRequestAttachment $record): string => Storage::disk('s3')->temporaryUrl($record->path, now()->addMinutes(5)))
                    ->openUrlInNewTab(),
            ]);
    }
}
