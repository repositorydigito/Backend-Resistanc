<?php

namespace App\Filament\Resources\ClassScheduleResource\RelationManagers;

use App\Models\ClassScheduleSeat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceRelationManager extends RelationManager
{
    protected static string $relationship = 'seatAssignments';

    protected static ?string $title = 'Control de Asistencia';

    protected static ?string $label = 'Asistencia';
    protected static ?string $pluralLabel = 'Asistencias';

    protected static ?string $icon = 'heroicon-o-clipboard-document-check';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'occupied' => '🔴 Ocupado',
                        'Completed' => '✅ Completado',
                    ])
                    ->required()
                    ->default('occupied'),

                Forms\Components\Toggle::make('attendance_confirmed')
                    ->label('Asistencia Confirmada')
                    ->helperText('Marcar si el usuario asistió a la clase')
                    ->default(false),

                Forms\Components\DateTimePicker::make('reserved_at')
                    ->label('Reservado en')
                    ->disabled(),

                Forms\Components\Textarea::make('attendance_notes')
                    ->label('Notas de Asistencia')
                    ->placeholder('Observaciones sobre la asistencia del usuario')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->modifyQueryUsing(function (Builder $query) {
                return $query
                    ->whereIn('status', ['occupied', 'Completed'])
                    ->whereNotNull('user_id')
                    ->with(['user', 'seat'])
                    ->join('seats', 'class_schedule_seat.seats_id', '=', 'seats.id')
                    ->orderBy('seats.row')
                    ->orderBy('seats.column')
                    ->select('class_schedule_seat.*');
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('seat.seat_number')
                    ->label('Asiento')
                    ->formatStateUsing(fn($record) => "Fila {$record->seat->row} - Col {$record->seat->column}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'occupied' => 'Ocupado',
                        'Completed' => 'Completado',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'occupied' => 'warning',
                        'Completed' => 'success',
                        default => 'secondary',
                    }),

                Tables\Columns\IconColumn::make('attendance_confirmed')
                    ->label('Asistencia')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn($record) => $record->status === 'Completed'),

                Tables\Columns\TextColumn::make('reserved_at')
                    ->label('Reservado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendance_notes')
                    ->label('Notas')
                    ->limit(30)
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'occupied' => 'Ocupado',
                        'Completed' => 'Completado',
                    ]),

                Tables\Filters\TernaryFilter::make('attendance_confirmed')
                    ->label('Asistencia Confirmada')
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', 'Completed'),
                        false: fn (Builder $query) => $query->where('status', 'occupied'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->headerActions([
                Tables\Actions\Action::make('startClass')
                    ->label('Iniciar Clase')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn() => $this->getOwnerRecord()->status === 'scheduled')
                    ->action(function () {
                        $this->startClass();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Iniciar Clase')
                    ->modalDescription('¿Estás seguro de que quieres iniciar la clase? Esto cambiará el estado a "En Progreso".')
                    ->modalSubmitActionLabel('Sí, Iniciar Clase'),

                Tables\Actions\Action::make('completeClass')
                    ->label('Finalizar Clase')
                    ->icon('heroicon-o-flag')
                    ->color('info')
                    ->visible(fn() => $this->getOwnerRecord()->status === 'in_progress')
                    ->action(function () {
                        $this->completeClass();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Finalizar Clase')
                    ->modalDescription('¿Estás seguro de que quieres finalizar la clase? Esto cambiará el estado a "Completado".')
                    ->modalSubmitActionLabel('Sí, Finalizar Clase'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar Asistencia'),

                Tables\Actions\Action::make('confirmAttendance')
                    ->label('Confirmar Asistencia')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'occupied')
                    ->action(function ($record) {
                        $this->confirmAttendance($record);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Asistencia')
                    ->modalDescription("¿Confirmar la asistencia de {$record->user->name}?")
                    ->modalSubmitActionLabel('Sí, Confirmar'),

                Tables\Actions\Action::make('markAbsent')
                    ->label('Marcar Ausente')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'occupied')
                    ->action(function ($record) {
                        $this->markAbsent($record);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Marcar Ausente')
                    ->modalDescription("¿Marcar como ausente a {$record->user->name}?")
                    ->modalSubmitActionLabel('Sí, Marcar Ausente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('confirmAllAttendance')
                        ->label('Confirmar Asistencia de Todos')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $this->confirmAllAttendance($records);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar Asistencia de Todos')
                        ->modalDescription('¿Confirmar la asistencia de todos los usuarios seleccionados?')
                        ->modalSubmitActionLabel('Sí, Confirmar Todos'),

                    Tables\Actions\BulkAction::make('markAllAbsent')
                        ->label('Marcar Ausentes a Todos')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $this->markAllAbsent($records);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Marcar Ausentes a Todos')
                        ->modalDescription('¿Marcar como ausentes a todos los usuarios seleccionados?')
                        ->modalSubmitActionLabel('Sí, Marcar Ausentes'),
                ]),
            ]);
    }

    protected function startClass(): void
    {
        $schedule = $this->getOwnerRecord();
        
        $schedule->update([
            'status' => 'in_progress'
        ]);

        Notification::make()
            ->title('Clase Iniciada')
            ->body('La clase ha sido iniciada exitosamente.')
            ->success()
            ->send();
    }

    protected function completeClass(): void
    {
        $schedule = $this->getOwnerRecord();
        
        $schedule->update([
            'status' => 'completed'
        ]);

        Notification::make()
            ->title('Clase Finalizada')
            ->body('La clase ha sido finalizada exitosamente.')
            ->success()
            ->send();
    }

    protected function confirmAttendance(ClassScheduleSeat $record): void
    {
        $record->update([
            'status' => 'Completed',
            'attendance_notes' => $record->attendance_notes ?? 'Asistencia confirmada'
        ]);

        Notification::make()
            ->title('Asistencia Confirmada')
            ->body("Asistencia de {$record->user->name} confirmada exitosamente.")
            ->success()
            ->send();
    }

    protected function markAbsent(ClassScheduleSeat $record): void
    {
        $record->update([
            'status' => 'Completed',
            'attendance_notes' => ($record->attendance_notes ?? '') . ' - Ausente'
        ]);

        Notification::make()
            ->title('Ausencia Marcada')
            ->body("{$record->user->name} marcado como ausente.")
            ->warning()
            ->send();
    }

    protected function confirmAllAttendance($records): void
    {
        $count = 0;
        foreach ($records as $record) {
            if ($record->status === 'occupied') {
                $record->update([
                    'status' => 'Completed',
                    'attendance_notes' => $record->attendance_notes ?? 'Asistencia confirmada'
                ]);
                $count++;
            }
        }

        Notification::make()
            ->title('Asistencias Confirmadas')
            ->body("Se confirmó la asistencia de {$count} usuarios.")
            ->success()
            ->send();
    }

    protected function markAllAbsent($records): void
    {
        $count = 0;
        foreach ($records as $record) {
            if ($record->status === 'occupied') {
                $record->update([
                    'status' => 'Completed',
                    'attendance_notes' => ($record->attendance_notes ?? '') . ' - Ausente'
                ]);
                $count++;
            }
        }

        Notification::make()
            ->title('Ausencias Marcadas')
            ->body("Se marcaron como ausentes {$count} usuarios.")
            ->warning()
            ->send();
    }
}
