<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Enums\ArticleStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(ArticleStatus::class)
                    ->default('draft')
                    ->required(),
                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->required()
                    ->searchable(),
                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),
            ]);
    }
}
