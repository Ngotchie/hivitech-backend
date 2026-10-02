<?php

namespace App\Filament\Resources\ContactMessages;

use App\Enums\ContactMessageStatus;
use App\Filament\Resources\ContactMessages\Pages\EditContactMessage;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
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

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?string $navigationLabel = 'Messages de contact';

    protected static ?string $modelLabel = 'message de contact';

    protected static ?string $pluralModelLabel = 'messages de contact';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->new()->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Expéditeur')->schema([TextInput::make('name')->label('Nom')->required(), TextInput::make('email')->label('E-mail')->email()->required(), TextInput::make('phone')->label('Téléphone')])->columns(3)->columnSpanFull(),
            Section::make('Message')->schema([TextInput::make('subject')->label('Sujet')->disabled()->dehydrated(false), Textarea::make('message')->label('Message')->disabled()->dehydrated(false)->rows(8)->columnSpanFull()])->columnSpanFull(),
            Section::make('Traitement')->schema([
                Select::make('status')->options(ContactMessageStatus::options())->required(), Select::make('assigned_to')->label('Assigné à')->relationship('assignee', 'name')->searchable()->preload(),
                DateTimePicker::make('read_at')->label('Lu le'), DateTimePicker::make('replied_at')->label('Répondu le'), DateTimePicker::make('closed_at')->label('Clôturé le'),
                Textarea::make('internal_notes')->label('Notes internes')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nom')->searchable(['name', 'email', 'phone', 'subject', 'message']), TextColumn::make('email')->searchable(), TextColumn::make('subject')->label('Sujet')->searchable(),
            TextColumn::make('status')->badge()->formatStateUsing(fn (ContactMessageStatus $state) => $state->label())->color(fn (ContactMessageStatus $state) => $state->color()),
            TextColumn::make('assignee.name')->label('Assigné à')->placeholder('Non assigné'), TextColumn::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(ContactMessageStatus::options()), SelectFilter::make('assigned_to')->label('Assigné à')->relationship('assignee', 'name')->searchable()->preload(),
            TernaryFilter::make('unassigned')->label('Non assigné')->queries(true: fn (Builder $query) => $query->whereNull('assigned_to'), false: fn (Builder $query) => $query->whereNotNull('assigned_to')),
            Filter::make('created_at')->label('Date')->schema([DatePicker::make('from')->label('Du'), DatePicker::make('until')->label('Au')])->query(fn (Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))),
        ])->recordActions([
            Action::make('read')->label('Marquer comme lu')->action(fn (ContactMessage $record) => $record->transitionTo(ContactMessageStatus::Read)),
            Action::make('assign')->label('M’affecter')->action(fn (ContactMessage $record) => $record->update(['assigned_to' => auth()->id()])),
            Action::make('reply')->label('Marquer comme répondu')->color('success')->action(fn (ContactMessage $record) => $record->transitionTo(ContactMessageStatus::Replied)),
            Action::make('close')->label('Clôturer')->color('gray')->action(fn (ContactMessage $record) => $record->transitionTo(ContactMessageStatus::Closed)),
            Action::make('spam')->label('Spam')->color('danger')->requiresConfirmation()->action(fn (ContactMessage $record) => $record->transitionTo(ContactMessageStatus::Spam)), EditAction::make(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListContactMessages::route('/'), 'edit' => EditContactMessage::route('/{record}/edit')];
    }
}
