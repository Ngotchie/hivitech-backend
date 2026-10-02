<?php

namespace App\Filament\Resources\Posts;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?string $navigationLabel = 'Articles';

    protected static ?string $modelLabel = 'article';

    protected static ?string $pluralModelLabel = 'articles';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', PostStatus::Review)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contenu')->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(255)->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                Textarea::make('excerpt')->label('Extrait')->columnSpanFull(),
                RichEditor::make('content')->label('Contenu')->columnSpanFull(),
                FileUpload::make('cover_image_path')->label('Image de couverture')->disk('s3')->directory('blog/covers')->image()->imageEditor()->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Classification')->schema([
                Select::make('post_category_id')->label('Catégorie')->relationship('category', 'name')->searchable()->preload(),
                Select::make('author_id')->label('Auteur')->relationship('author', 'name')->searchable()->preload(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Publication')->schema([
                Select::make('status')->label('Statut')->options(PostStatus::options())->default(PostStatus::Draft->value)->required(),
                DateTimePicker::make('published_at')->label('Publication prévue')->helperText('Une date future programme la publication.'),
                Toggle::make('is_featured')->label('À la une')->default(false),
            ])->columns(3)->columnSpanFull(),
            Section::make('SEO')->schema([
                TextInput::make('seo_title')->label('Titre SEO')->maxLength(255)->helperText('Titre affiché dans les résultats des moteurs de recherche.'),
                Textarea::make('seo_description')->label('Description SEO')->helperText('Résumé utilisé par les moteurs de recherche et lors du partage.')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Statistiques')->schema([
                TextInput::make('reading_time')->label('Temps de lecture')->suffix('min')->disabled()->dehydrated(false),
                TextInput::make('views_count')->label('Nombre de vues')->disabled()->dehydrated(false),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(['title', 'slug', 'excerpt'])->sortable(),
                TextColumn::make('category.name')->label('Catégorie')->sortable(),
                TextColumn::make('author.name')->label('Auteur')->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn (PostStatus $state): string => $state->label())->color(fn (PostStatus $state): string => $state->color()),
                IconColumn::make('is_featured')->label('À la une')->boolean(),
                TextColumn::make('published_at')->label('Publication')->dateTime('d/m/Y H:i')->sortable()->placeholder('—'),
                TextColumn::make('reading_time')->label('Lecture')->suffix(' min'),
                TextColumn::make('views_count')->label('Vues')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('Mise à jour')->dateTime('d/m/Y H:i')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PostStatus::options()),
                SelectFilter::make('post_category_id')->label('Catégorie')->relationship('category', 'name')->searchable()->preload(),
                SelectFilter::make('author_id')->label('Auteur')->relationship('author', 'name')->searchable()->preload(),
                TernaryFilter::make('is_featured')->label('À la une')->boolean(),
                Filter::make('scheduled')->label('Programmés')->query(fn (Builder $query): Builder => $query->scheduled()),
                Filter::make('published_today')->label('Publiés aujourd’hui')->query(fn (Builder $query): Builder => $query->published()->whereDate('published_at', today())),
            ])
            ->recordActions([
                Action::make('publish')->label('Publier maintenant')->color('success')->action(fn (Post $record) => $record->publishNow()),
                Action::make('draft')->label('Passer en brouillon')->color('gray')->action(fn (Post $record) => $record->changeStatus(PostStatus::Draft)),
                Action::make('archive')->label('Archiver')->color('danger')->requiresConfirmation()->action(fn (Post $record) => $record->changeStatus(PostStatus::Archived)),
                Action::make('feature')->label(fn (Post $record): string => $record->is_featured ? 'Retirer de la une' : 'Mettre à la une')->action(fn (Post $record) => $record->update(['is_featured' => ! $record->is_featured])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([DeleteBulkAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
