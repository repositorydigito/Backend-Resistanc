<?php

namespace App\Filament\Resources\ClassScheduleResource\Pages;

use App\Filament\Resources\ClassScheduleResource;
use App\Models\ClassSchedule;
use App\Models\ClassScheduleSeat;
use App\Models\Seat;
use Filament\Actions;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ManageSeats extends Page
{
    protected static string $resource = ClassScheduleResource::class;

    protected static string $view = 'filament.resources.class-schedule-resource.pages.manage-seats';

    protected static ?string $title = 'Gestión Visual de Asientos';

    public ClassSchedule $record;

    public $seatMap = [];
    public $studioInfo = [];
    public $reservationStats = [];

    public function mount(int|string $record): void
    {
        $this->record = ClassSchedule::findOrFail($record);
        $this->loadSeatMap();
        $this->loadStudioInfo();
        $this->loadReservationStats();
    }

    public function getTitle(): string|Htmlable
    {
        return "Asientos - {$this->record->class->name} ({$this->record->scheduled_date->format('d/m/Y')} {$this->record->start_time})";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateSeats')
                ->label('Generar Asientos Automáticamente')
                ->icon('heroicon-o-squares-plus')
                ->color('info')
                ->action('generateAllSeats')
                ->requiresConfirmation()
                ->modalHeading('Generar Asientos')
                ->modalDescription('Esto creará automáticamente asientos para todos los asientos activos del estudio. ¿Continuar?'),

            Actions\Action::make('releaseExpired')
                ->label('Liberar Expirados')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->action('releaseExpiredReservations'),

            Actions\Action::make('refreshMap')
                ->label('Actualizar Mapa')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshSeatMap'),

            Actions\Action::make('backToSchedule')
                ->label('Volver al Horario')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => ClassScheduleResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function loadSeatMap(): void
    {
        $studio = $this->record->studio;

        // Inicializar mapa vacío
        $this->seatMap = [];
        for ($row = 1; $row <= $studio->row; $row++) {
            for ($col = 1; $col <= $studio->column; $col++) {
                $this->seatMap[$row][$col] = [
                    'exists' => false,
                    'seat_id' => null,
                    'seat_number' => null,
                    'status' => 'empty',
                    'user_name' => null,
                    'reserved_at' => null,
                    'expires_at' => null,
                    'assignment_id' => null,
                ];
            }
        }

        // Llenar con asientos reales
        $seatAssignments = $this->record->seatAssignments()
            ->with(['seat', 'user'])
            ->get();

        foreach ($seatAssignments as $assignment) {
            $seat = $assignment->seat;
            if ($seat && $seat->row <= $studio->row && $seat->column <= $studio->column) {
                $this->seatMap[$seat->row][$seat->column] = [
                    'exists' => true,
                    'seat_id' => $seat->id,
                    'seat_identifier' => "R{$seat->row}C{$seat->column}",
                    'status' => $assignment->status,
                    'user_name' => $assignment->user?->name,
                    'reserved_at' => $assignment->reserved_at,
                    'expires_at' => $assignment->expires_at,
                    'assignment_id' => $assignment->id,
                    'is_expired' => $assignment->isExpired(),
                ];
            }
        }
    }

    public function loadStudioInfo(): void
    {
        $studio = $this->record->studio;
        $this->studioInfo = [
            'name' => $studio->name,
            'capacity' => $studio->capacity_per_seat ?? $studio->max_capacity,
            'rows' => $studio->row,
            'columns' => $studio->column,
            'total_positions' => $studio->row * $studio->column,
        ];
    }

    public function loadReservationStats(): void
    {
        $assignments = $this->record->seatAssignments;

        $this->reservationStats = [
            'total_seats' => $assignments->count(),
            'available' => $assignments->where('status', 'available')->count(),
            'reserved' => $assignments->where('status', 'reserved')->count(),
            'occupied' => $assignments->where('status', 'occupied')->count(),
            'completed' => $assignments->where('status', 'Completed')->count(),
            'blocked' => $assignments->where('status', 'blocked')->count(),
            'expired' => $assignments->filter(fn($a) => $a->isExpired())->count(),
        ];
    }

    public function generateAllSeats(): void
    {
        $studio = $this->record->studio;

        // Obtener todos los asientos activos del estudio
        $seats = Seat::where('studio_id', $studio->id)
            ->where('is_active', true)
            ->get();

        $created = 0;
        foreach ($seats as $seat) {
            // Solo crear si no existe ya
            $exists = ClassScheduleSeat::where('class_schedules_id', $this->record->id)
                ->where('seats_id', $seat->id)
                ->exists();

            if (!$exists) {
                ClassScheduleSeat::create([
                    'class_schedules_id' => $this->record->id,
                    'seats_id' => $seat->id,
                    'status' => 'available',
                ]);
                $created++;
            }
        }

        $this->refreshSeatMap();

        Notification::make()
            ->title('Asientos Generados')
            ->body("Se generaron {$created} asientos automáticamente.")
            ->success()
            ->send();
    }

    public function releaseExpiredReservations(): void
    {
        $count = $this->record->releaseExpiredReservations();
        $this->refreshSeatMap();

        Notification::make()
            ->title('Reservas Liberadas')
            ->body("Se liberaron {$count} reservas expiradas.")
            ->success()
            ->send();
    }

    public function refreshSeatMap(): void
    {
        $this->loadSeatMap();
        $this->loadReservationStats();

        Notification::make()
            ->title('Mapa Actualizado')
            ->body('El mapa de asientos se ha actualizado.')
            ->success()
            ->send();
    }

    public function reserveSeat(int $assignmentId): void
    {
        $assignment = ClassScheduleSeat::find($assignmentId);

        if (!$assignment || $assignment->status !== 'available') {
            Notification::make()
                ->title('Error')
                ->body('El asiento no está disponible.')
                ->danger()
                ->send();
            return;
        }

        // Aquí podrías abrir un modal para seleccionar usuario
        // Por ahora, solo cambiar a reservado sin usuario
        $assignment->update([
            'status' => 'reserved',
            'reserved_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->refreshSeatMap();

        Notification::make()
            ->title('Asiento Reservado')
            ->body('El asiento ha sido reservado temporalmente.')
            ->success()
            ->send();
    }

    public function releaseSeat(int $assignmentId): void
    {
        $assignment = ClassScheduleSeat::find($assignmentId);

        if (!$assignment) {
            Notification::make()
                ->title('Error')
                ->body('Asiento no encontrado.')
                ->danger()
                ->send();
            return;
        }

        $assignment->release();
        $this->refreshSeatMap();

        Notification::make()
            ->title('Asiento Liberado')
            ->body('El asiento ha sido liberado.')
            ->success()
            ->send();
    }

    public function confirmSeat(int $assignmentId): void
    {
        $assignment = ClassScheduleSeat::find($assignmentId);

        if (!$assignment || $assignment->status !== 'reserved') {
            Notification::make()
                ->title('Error')
                ->body('Solo se pueden confirmar asientos reservados.')
                ->danger()
                ->send();
            return;
        }

        $assignment->confirm();
        $this->refreshSeatMap();

        Notification::make()
            ->title('Reserva Confirmada')
            ->body('La reserva ha sido confirmada.')
            ->success()
            ->send();
    }
}
