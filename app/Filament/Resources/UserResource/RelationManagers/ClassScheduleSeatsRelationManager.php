<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClassScheduleSeatsRelationManager extends RelationManager
{
    protected static string $relationship = 'classScheduleSeats';



    protected static ?string $title = 'Clases Reservadas'; // Título del administrador de relación

    protected static ?string $modelLabel = 'Clase Reservada'; // Nombre en singular
    protected static ?string $pluralModelLabel = 'Clases Reservadas'; // Nombre en plural

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([


                Tables\Columns\TextColumn::make('classSchedule.class.name')
                    ->label('Clase')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('classSchedule.studio.name')
                    ->label('Estudio')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('classSchedule.Package.name')
                    ->label('Paquete utilizado')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('classSchedule.scheduled_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable()
                    ->searchable(),


                Tables\Columns\TextColumn::make('classSchedule.start_time')
                    ->label('Hora Inicio')
                    ->time()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('classSchedule.end_time')
                    ->label('Hora Fin')
                    ->time()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('seat.row')
                    ->label('Fila')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('seat.column')
                    ->label('Columna')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'reserved' => 'Reservado',
                        'occupied' => 'Ocupado',
                        'Completed' => 'Completado',
                        'blocked' => 'Bloqueado',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'reserved' => 'warning',
                        'occupied' => 'danger',
                        'Completed' => 'info',
                        'blocked' => 'gray',
                        default => 'secondary',
                    })
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
                Tables\Actions\Action::make('release')
                    ->label('Liberar')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function ($record) {
                        $record->update([
                            'user_id' => null,
                            'status' => 'available',
                            'reserved_at' => null,
                            'expires_at' => null,
                        ]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
