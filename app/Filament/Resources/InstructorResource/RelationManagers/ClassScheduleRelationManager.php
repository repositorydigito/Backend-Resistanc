<?php

namespace App\Filament\Resources\InstructorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClassScheduleRelationManager extends RelationManager
{
    protected static string $relationship = 'ClassSchedules';



    protected static ?string $title = 'Horarios de Clase';

    protected static ?string $modelLabel = 'Horario de Clase';

    protected static ?string $pluralModelLabel = 'Horarios de Clase';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('class_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('class_id')
            ->columns([
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Clase')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Fecha')
                    ->date('Y-m-d')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Hora de Inicio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Hora de Fin')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('studio.name')
                    ->label('Sala')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('studio.location')
                    ->label('Ubicación')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'scheduled' => 'Programado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'postponed' => 'Pospuesto',
                        default => 'Desconocido',
                    })
                    ->color(fn($state) => match ($state) {
                        'scheduled' => 'primary',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'postponed' => 'secondary',
                        default => 'gray',
                    }),

            ])
            ->defaultSort('scheduled_date', 'asc')
            ->filters([

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Programado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'postponed' => 'Pospuesto',
                    ])->default('scheduled'),

            ])->headerActions([
                // Tables\Actions\CreateAction::make(),

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
