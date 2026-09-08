<?php

namespace App\Filament\Resources\Posts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Models\Post;
use Filament\Actions\Action;




class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
    TextColumn::make('titulo')
        ->label('Título')
        ->searchable()
        ->sortable(),
    TextColumn::make('categoria.nombre')
        ->label('Categoría'),
    IconColumn::make('publicado')
        ->label('Publicado')
        ->boolean(),
    TextColumn::make('resumen')
        ->label('Resumen')
        ->limit(40),
    TextColumn::make('created_at')
        ->label('Creado')
        ->dateTime('d/m/Y')
        ->sortable(),
])
            ->filters([
                SelectFilter::make('categoria_id')
    ->label('Categoría')
    ->relationship('categoria', 'nombre')
    ->preload(),
    TernaryFilter::make('publicado')
    ->label('¿Publicados?')
    ->trueLabel('Solo publicados')
    ->falseLabel('Solo borradores'),

           ])
->recordActions([
    Action::make('publicar')
    ->label('Publicar')
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->requiresConfirmation()
    ->visible(fn (Post $record) => ! $record->publicado)
    ->action(fn (Post $record) => $record->update(['publicado' => true])),
    EditAction::make(),
    DeleteAction::make(),
])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
