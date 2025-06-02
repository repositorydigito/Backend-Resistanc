<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserPackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'userPackages';

    protected static ?string $title = 'Paquetes de Usuario';

    protected static ?string $modelLabel = 'Paquete de Usuario'; // Nombre en singular
    protected static ?string $pluralModelLabel = 'Paquetes de Usuario'; // Nombre en plural

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('package.name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('package_code')
            ->columns([
                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paquete')
                    ->searchable(),


            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
