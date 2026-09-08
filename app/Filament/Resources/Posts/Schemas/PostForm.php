<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),
                Select::make('categoria_id')
                    ->label('Categoría')
                    ->relationship('categoria', 'nombre')
                    ->required(),
                Textarea::make('contenido')
                    ->label('Contenido')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('publicado')
                    ->label('Publicado')
                    ->default(true),
                TextInput::make('resumen')
                    ->label('Resumen')
                    ->maxLength(160)
                    ->columnSpanFull(),
            ]);
    }
}
