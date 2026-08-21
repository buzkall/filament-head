<?php

namespace Arzcode\FilamentHead\Tests\Fixtures\Resources;

use Arzcode\FilamentHead\Schemas\HeadMetadataFields;
use Arzcode\FilamentHead\Tests\Fixtures\Models\Post;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\CreatePost;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\EditPost;
use Arzcode\FilamentHead\Tests\Fixtures\Resources\Pages\ListPosts;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostResource extends Resource
{
    /**
     * Lets a test reshape the section — ->without(), ->locales() — before it is built.
     */
    public static ?Closure $configureFields = null;

    protected static ?string $model = Post::class;

    public static function form(Schema $schema): Schema
    {
        $fields = HeadMetadataFields::make();

        if (static::$configureFields !== null) {
            $fields = (static::$configureFields)($fields);
        }

        return $schema->components([
            TextInput::make('title')->required(),
            $fields,
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
