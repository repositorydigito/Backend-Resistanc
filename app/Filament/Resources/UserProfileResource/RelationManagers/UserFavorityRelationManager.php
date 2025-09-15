<?php

namespace App\Filament\Resources\UserProfileResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserFavorityRelationManager extends RelationManager
{
    protected static string $relationship = 'userFavorites';

    protected static ?string $title = 'Favoritos';
    protected static ?string $modelLabel = 'Favorito';
    protected static ?string $pluralModelLabel = 'Favoritos';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('notes')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('notes')
            ->columns([

                Tables\Columns\TextColumn::make('favoritable_type')
                    ->label('Tipo de Favorito')
                    // ->formatStateUsing(function ($state) {
                    //     return class_basename($state);
                    // })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'App\Models\Drink' => 'Bebida',
                            'App\Models\Product' => 'Producto',
                            'App\Models\ClassModel' => 'Clase',
                            'App\Models\Instructor' => 'Instructor',
                            default => 'Desconocido',
                        };
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('favoritable.name')
                    ->label('Favorito')

                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('notes'),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridad')
                    ->sortable()
                    ->searchable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
