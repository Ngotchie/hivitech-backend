<?php

namespace App\Filament\Resources\QuoteRequests;

use App\Enums\PreferredContactMethod;
use App\Enums\QuoteRequestStatus;
use App\Filament\Resources\QuoteRequests\Pages\EditQuoteRequest;
use App\Filament\Resources\QuoteRequests\Pages\ListQuoteRequests;
use App\Filament\Resources\QuoteRequests\RelationManagers\QuoteRequestAttachmentsRelationManager;
use App\Filament\Resources\QuoteRequests\RelationManagers\QuoteRequestNotesRelationManager;
use App\Models\QuoteRequest;
use App\Services\QuoteRequestService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?string $navigationLabel = 'Demandes de devis';

    protected static ?string $modelLabel = 'demande de devis';

    protected static ?string $pluralModelLabel = 'demandes de devis';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', QuoteRequestStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Demandeur')->schema([
                TextInput::make('first_name')->label('Prénom')->required()->maxLength(255),
                TextInput::make('last_name')->label('Nom')->maxLength(255),
                TextInput::make('company_name')->label('Entreprise')->maxLength(255),
                TextInput::make('email')->label('E-mail')->email()->required()->maxLength(255),
                TextInput::make('phone')->label('Téléphone')->maxLength(50),
                Select::make('preferred_contact_method')->label('Contact préféré')->options(PreferredContactMethod::options()),
            ])->columns(2)->columnSpanFull(),
            Section::make('Demande')->schema([
                TextInput::make('reference')->disabled()->dehydrated(false),
                Select::make('service_id')->label('Service')->relationship('service', 'name')->searchable()->preload(),
                TextInput::make('subject')->label('Objet')->maxLength(255),
                Textarea::make('message')->label('Message')->required()->rows(8)->columnSpanFull(),
                TextInput::make('budget')->numeric()->suffix('FCFA'),
                DatePicker::make('desired_deadline')->label('Échéance souhaitée'),
            ])->columns(2)->columnSpanFull(),
            Section::make('Traitement commercial')->schema([
                Select::make('status')->label('Statut')->options(QuoteRequestStatus::options())->required(),
                Select::make('assigned_to')->label('Assignée à')->relationship('assignee', 'name')->searchable()->preload(),
                DateTimePicker::make('contacted_at')->label('Contactée le'),
                DateTimePicker::make('quoted_at')->label('Devis envoyé le'),
                DateTimePicker::make('accepted_at')->label('Acceptée le'),
                DateTimePicker::make('closed_at')->label('Clôturée le'),
            ])->columns(2)->columnSpanFull(),
            Section::make('Synthèse interne')->schema([
                Textarea::make('internal_notes')->label('Notes de synthèse')->rows(6)->columnSpanFull(),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->sortable(),
                TextColumn::make('requester')->label('Demandeur')->state(fn (QuoteRequest $record): string => trim("{$record->first_name} {$record->last_name}"))->searchable(['first_name', 'last_name', 'email', 'phone']),
                TextColumn::make('company_name')->label('Entreprise')->searchable()->toggleable(),
                TextColumn::make('service.name')->label('Service')->sortable(),
                TextColumn::make('budget')->money('XAF')->sortable()->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn (QuoteRequestStatus $state): string => $state->label())->color(fn (QuoteRequestStatus $state): string => $state->color()),
                TextColumn::make('assignee.name')->label('Assignée à')->placeholder('Non assignée'),
                TextColumn::make('preferred_contact_method')->label('Contact')->formatStateUsing(fn (?PreferredContactMethod $state): ?string => $state?->label()),
                TextColumn::make('created_at')->label('Reçue le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(QuoteRequestStatus::options())->multiple(),
                SelectFilter::make('service_id')->label('Service')->relationship('service', 'name')->searchable()->preload(),
                SelectFilter::make('assigned_to')->label('Assignée à')->relationship('assignee', 'name')->searchable()->preload(),
                TernaryFilter::make('unassigned')
                    ->label('Non assignée')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('assigned_to'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('assigned_to'),
                    ),
                Filter::make('created_at')->label('Date de réception')->schema([
                    DatePicker::make('from')->label('Du'),
                    DatePicker::make('until')->label('Au'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                Action::make('assignToMe')->label('M’affecter')->icon(Heroicon::OutlinedUserPlus)
                    ->action(fn (QuoteRequest $record) => $record->update(['assigned_to' => auth()->id()])),
                Action::make('review')->label('Mettre en analyse')->color('warning')
                    ->action(fn (QuoteRequest $record, QuoteRequestService $service) => $service->transition($record, QuoteRequestStatus::Reviewing)),
                Action::make('quoted')->label('Devis envoyé')->color('info')
                    ->action(fn (QuoteRequest $record, QuoteRequestService $service) => $service->transition($record, QuoteRequestStatus::Quoted)),
                Action::make('accept')->label('Accepter')->color('success')
                    ->action(fn (QuoteRequest $record, QuoteRequestService $service) => $service->transition($record, QuoteRequestStatus::Accepted)),
                Action::make('reject')->label('Refuser')->color('danger')->requiresConfirmation()
                    ->action(fn (QuoteRequest $record, QuoteRequestService $service) => $service->transition($record, QuoteRequestStatus::Rejected)),
                Action::make('close')->label('Clôturer')->color('gray')->requiresConfirmation()
                    ->action(fn (QuoteRequest $record, QuoteRequestService $service) => $service->transition($record, QuoteRequestStatus::Closed)),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [QuoteRequestNotesRelationManager::class, QuoteRequestAttachmentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuoteRequests::route('/'),
            'edit' => EditQuoteRequest::route('/{record}/edit'),
        ];
    }
}
