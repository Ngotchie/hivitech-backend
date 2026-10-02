<?php

namespace App\Filament\Resources\Pages;

use App\Enums\PageStatus;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contenu')->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(255)->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state ?? '')) : null),
                TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                RichEditor::make('content')->label('Contenu')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Publication')->schema([
                Select::make('status')->options(PageStatus::options())->default(PageStatus::Draft->value)->required(),
                DateTimePicker::make('published_at')->label('Publication'),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->required(),
            ])->columns(3)->columnSpanFull(),
            Section::make('SEO')->schema([
                TextInput::make('seo_title')->label('Titre SEO')->maxLength(255),
                Textarea::make('seo_description')->label('Description SEO')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Titre')->searchable()->sortable(), TextColumn::make('slug')->searchable()->sortable(),
            TextColumn::make('status')->badge()->formatStateUsing(fn (PageStatus $state) => $state->label())->color(fn (PageStatus $state) => $state->color()),
            TextColumn::make('published_at')->label('Publication')->dateTime('d/m/Y H:i')->sortable()->placeholder('—'),
            TextColumn::make('sort_order')->label('Ordre')->sortable(), TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i'),
        ])->filters([SelectFilter::make('status')->options(PageStatus::options())])
            ->recordActions([EditAction::make(), DeleteAction::make()])->toolbarActions([DeleteBulkAction::make()])->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return ['index' => ListPages::route('/'), 'create' => CreatePage::route('/create'), 'edit' => EditPage::route('/{record}/edit')];
    }
}
