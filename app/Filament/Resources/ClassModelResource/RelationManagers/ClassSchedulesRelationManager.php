<?php

namespace App\Filament\Resources\ClassModelResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClassSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'classSchedules';


    protected static ?string $navigationLabel = 'Horarios de Clase';
    protected static ?string $label = 'Horario';
    protected static ?string $pluralLabel = 'Horarios';


    protected static ?string $title = 'Horarios de Clase';

    protected static ?string $modelLabel = 'Horario';

    protected static ?string $pluralModelLabel = 'Horarios';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Horario')
                    ->schema([
                        Forms\Components\Select::make('instructor_id')
                            ->label('Instructor')
                            ->relationship('instructor', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('studio_id')
                            ->label('Sala/Estudio')
                            ->relationship('studio', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('scheduled_date')
                            ->label('Fecha Programada')
                            ->required()
                            ->default(now()),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Hora de Inicio')
                            ->required()
                            ->seconds(false),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Hora de Fin')
                            ->required()
                            ->seconds(false),

                        Forms\Components\TextInput::make('max_capacity')
                            ->label('Capacidad Máxima')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100),

                        Forms\Components\TextInput::make('available_spots')
                            ->label('Lugares Disponibles')
                            ->required()
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('booked_spots')
                            ->label('Lugares Reservados')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('waitlist_spots')
                            ->label('Lista de Espera')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\DateTimePicker::make('booking_opens_at')
                            ->label('Reservas Abren')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('booking_closes_at')
                            ->label('Reservas Cierran')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('cancellation_deadline')
                            ->label('Límite de Cancelación')
                            ->nullable(),

                        Forms\Components\Toggle::make('is_holiday_schedule')
                            ->label('Horario de Feriado')
                            ->default(false),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'scheduled' => 'Programado',
                                'in_progress' => 'En Progreso',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                                'postponed' => 'Pospuesto',
                            ])
                            ->default('scheduled')
                            ->required(),

                        Forms\Components\Textarea::make('special_notes')
                            ->label('Notas Especiales')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('scheduled_date')
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Hora Inicio')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Hora Fin')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('instructor.name')
                    ->label('Instructor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('studio.name')
                    ->label('Sala')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_capacity')
                    ->label('Capacidad')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_spots')
                    ->label('Disponibles')
                    ->numeric()
                    ->sortable()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('booked_spots')
                    ->label('Reservados')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'scheduled' => 'Programado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'postponed' => 'Pospuesto',
                        default => $state
                    })

                    ->colors([
                        'gray' => 'scheduled',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'info' => 'postponed',
                    ])
                    ->badge(),

                Tables\Columns\IconColumn::make('is_holiday_schedule')
                    ->label('Feriado')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('status')
                //     ->label('Estado')
                //     ->options([
                //         'scheduled' => 'Programado',
                //         'in_progress' => 'En Progreso',
                //         'completed' => 'Completado',
                //         'cancelled' => 'Cancelado',
                //         'postponed' => 'Pospuesto',
                //     ]),

                // Tables\Filters\SelectFilter::make('instructor')
                //     ->relationship('instructor', 'name')
                //     ->label('Instructor'),

                // Tables\Filters\Filter::make('this_week')
                //     ->label('Esta Semana')
                //     ->query(fn(Builder $query): Builder => $query
                //         ->whereBetween('scheduled_date', [
                //             now()->startOfWeek(),
                //             now()->endOfWeek()
                //         ])),

                // Tables\Filters\Filter::make('available_only')
                //     ->label('Solo Disponibles')
                //     ->query(fn(Builder $query): Builder => $query
                //         ->where('available_spots', '>', 0)
                //         ->where('status', 'scheduled')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear Horario'),
            ])
            ->actions([
                // Tables\Actions\Action::make('mark_completed')
                //     ->label('Marcar Completado')
                //     ->icon('heroicon-o-check-circle')
                //     ->color('success')
                //     ->visible(fn($record) => $record->status === 'scheduled' || $record->status === 'in_progress')
                //     ->action(fn($record) => $record->update(['status' => 'completed']))
                //     ->requiresConfirmation(),

                // Tables\Actions\Action::make('cancel')
                //     ->label('Cancelar')
                //     ->icon('heroicon-o-x-circle')
                //     ->color('danger')
                //     ->visible(fn($record) => $record->status === 'scheduled')
                //     ->action(fn($record) => $record->update(['status' => 'cancelled']))
                //     ->requiresConfirmation(),

                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('Marcar como Completados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($records) => $records->each->update(['status' => 'completed']))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('scheduled_date', 'asc')
            ->emptyStateHeading('Sin horarios programados')
            ->emptyStateDescription('No hay horarios creados para esta clase.')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
