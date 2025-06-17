<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserWaitingClassRelationManager extends RelationManager
{
    protected static string $relationship = 'waitingClasses';

    protected static ?string $title = 'Lista de espera';

    protected static ?string $modelLabel = 'Lista de espera'; // Nombre en singular
    protected static ?string $pluralModelLabel = 'Listas de espera'; // Nombre en plural


    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('class_schedules_id')
                    ->relationship('classSchedule', 'name')
                    ->required(),

                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),

                Forms\Components\Select::make('user_package_id')
                    ->relationship('userPackage', 'name')
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->options([
                        'waiting' => 'En espera',
                        'notified' => 'Notificado',
                        'confirmed' => 'Confirmado',
                        'expired' => 'Expirado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('waiting')
                    ->required(),
            ])->columns([
                'sm' => 2,
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user_id')
            ->columns([
                Tables\Columns\TextColumn::make('classSchedule.name')
                    ->label('Clase')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('userPackage.name')
                    ->label('Paquete')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')

                    ->sortable(),
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
