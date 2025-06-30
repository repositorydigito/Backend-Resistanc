<?php

namespace App\Livewire;

use App\Models\ClassSchedule;
use App\Models\ClassScheduleSeat;
use App\Models\User;
use App\Models\WaitingClass;
use Livewire\Component;
use Filament\Notifications\Notification;


class ScheduleSeatMapComponent extends Component
{
    public ClassSchedule $schedule;
    public $seatMap = [];
    public $studioInfo = [];
    public $reservationStats = [];
    public $waitingListStats = [];
    public $rows;
    public $columns;

    // Modal de selección de usuario
    public $showUserModal = false;
    public $selectedAssignmentId = null;
    public $selectedUserId = null;
    public $reservationMinutes = 15;
    public $userSearch = '';
    public $availableUsers = [];

    // --- NUEVO: Modal de reasignación ---
    public $showReassignModal = false;
    public $reassignAssignmentId = null;
    public $selectedWaitingUserId = null;
    public $waitingListForReassign = [];

    public function mount(ClassSchedule $schedule)
    {
        $this->schedule = $schedule;
        $this->loadSeatMap();
        $this->loadStudioInfo();
        $this->loadReservationStats();
        $this->loadWaitingListStats();
        $this->loadAvailableUsers();
    }

    // 🆕 Método para refrescar todo el componente
    public function refreshSeatMap()
    {
        // Recargar el horario desde la base de datos para obtener cambios
        $this->schedule = $this->schedule->fresh();

        $this->loadSeatMap();
        $this->loadStudioInfo();
        $this->loadReservationStats();
        $this->loadWaitingListStats();

        // Emitir evento para notificar que se actualizó
        $this->dispatch('seatMapRefreshed');
    }

    public function loadSeatMap()
    {
        $studio = $this->schedule->studio;
        $this->rows = $studio->row ?? 0;
        $this->columns = $studio->column ?? 0;

        // Inicializar mapa vacío
        $this->seatMap = [];
        for ($row = 1; $row <= $this->rows; $row++) {
            for ($col = 1; $col <= $this->columns; $col++) {
                $this->seatMap[$row][$col] = [
                    'exists' => false,
                    'seat_id' => null,
                    'assignment_id' => null,
                    'status' => null,
                    'user_name' => null,
                    'reserved_at' => null,
                    'expires_at' => null,
                    'is_expired' => false,
                ];
            }
        }

        // Cargar asientos asignados
        $seatAssignments = $this->schedule->seatAssignments()
            ->with(['seat', 'user', 'userPackage.package'])
            ->get();

        foreach ($seatAssignments as $assignment) {
            $seat = $assignment->seat;
            if ($seat && $seat->row <= $this->rows && $seat->column <= $this->columns) {
                $this->seatMap[$seat->row][$seat->column] = [
                    'exists' => true,
                    'seat_id' => $seat->id,
                    'assignment_id' => $assignment->id,
                    'seat_identifier' => "R{$seat->row}C{$seat->column}",
                    'status' => $assignment->status,
                    'user_name' => $assignment->user?->name,
                    'user_email' => $assignment->user?->email,
                    'user_package_name' => $assignment->userPackage?->package->name,
                    'user_package_remaining' => $assignment->userPackage?->remaining_classes,
                    'reserved_at' => $assignment->reserved_at,
                    'expires_at' => $assignment->expires_at,
                    'is_expired' => $assignment->isExpired(),
                ];
            }
        }
    }

    public function loadStudioInfo()
    {
        $studio = $this->schedule->studio;
        $this->studioInfo = [
            'name' => $studio->name,
            'capacity' => $studio->capacity_per_seat ?? $studio->max_capacity,
            'rows' => $studio->row,
            'columns' => $studio->column,
            'total_positions' => $studio->row * $studio->column,
            'addressing' => $studio->addressing ?? 'left_to_right',
        ];
    }

    public function loadReservationStats()
    {
        $assignments = $this->schedule->seatAssignments();

        $this->reservationStats = [
            'total_seats' => $assignments->count(),
            'available' => $assignments->where('status', 'available')->count(),
            'reserved' => $assignments->where('status', 'reserved')->count(),
            'occupied' => $assignments->where('status', 'occupied')->count(),
            'completed' => $assignments->where('status', 'Completed')->count(),
            'blocked' => $assignments->where('status', 'blocked')->count(),
            'expired' => $assignments->expired()->count(),
        ];
    }

    // 🆕 Cargar estadísticas de lista de espera
    public function loadWaitingListStats()
    {
        $this->waitingListStats = [
            'total_waiting' => $this->schedule->waitingUsers()->where('status', 'waiting')->count(),
            'total_notified' => $this->schedule->waitingUsers()->where('status', 'notified')->count(),
            'total_confirmed' => $this->schedule->waitingUsers()->where('status', 'confirmed')->count(),
            'waiting_users' => $this->schedule->waitingUsers()
                ->whereIn('status', ['waiting', 'notified'])
                ->with(['user', 'userPackage.package'])
                ->orderBy('created_at', 'asc')
                ->get()
        ];
    }

    public function openReservationModal($assignmentId)
    {
        $assignment = ClassScheduleSeat::findOrFail($assignmentId);

        if ($assignment->status !== 'available') {
            Notification::make()
                ->title('Error')
                ->body('Este asiento no está disponible')
                ->danger()
                ->send();
            return;
        }

        $this->selectedAssignmentId = $assignmentId;
        $this->selectedUserId = null;
        $this->reservationMinutes = 15;
        $this->userSearch = '';
        $this->loadAvailableUsers();
        $this->showUserModal = true;
    }

    public function loadAvailableUsers()
    {
        $query = User::query();

        // Filtrar solo usuarios con el rol "Cliente"
        $query->whereHas('roles', function($q) {
            $q->where('name', 'Cliente');
        });

        if (!empty($this->userSearch)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->userSearch . '%')
                  ->orWhere('email', 'like', '%' . $this->userSearch . '%');
            });
        }

        // Convertir a array manualmente para asegurar que tenemos todos los campos
        $users = $query->with('roles')->limit(20)->get();
        $this->availableUsers = $users->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name ?? 'Sin nombre',
                'email' => $user->email ?? 'Sin email',
                'roles' => $user->roles->pluck('name')->join(', '),
                'document_number' => null, // Los users no tienen document_number
            ];
        })->toArray();
    }

    public function updatedUserSearch()
    {
        $this->loadAvailableUsers();
    }

    public function reserveSeat()
    {
        if (!$this->selectedUserId) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un usuario')
                ->danger()
                ->send();
            return;
        }

        try {
            $assignment = ClassScheduleSeat::findOrFail($this->selectedAssignmentId);
            $assignment->reserve($this->selectedUserId, $this->reservationMinutes);

            $user = User::find($this->selectedUserId);
            $this->refreshData();
            $this->closeModal();

            Notification::make()
                ->title('Asiento Reservado')
                ->body("Asiento {$assignment->seat->row}.{$assignment->seat->column} reservado para {$user->name}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo reservar el asiento: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function closeModal()
    {
        $this->showUserModal = false;
        $this->selectedAssignmentId = null;
        $this->selectedUserId = null;
        $this->userSearch = '';
        $this->availableUsers = [];
    }

    // Método para reserva rápida (para casos especiales)
    public function quickReserveSeat($assignmentId, $userId)
    {
        try {
            $assignment = ClassScheduleSeat::findOrFail($assignmentId);

            if ($assignment->status !== 'available') {
                Notification::make()
                    ->title('Error')
                    ->body('Este asiento no está disponible')
                    ->danger()
                    ->send();
                return;
            }

            $assignment->reserve($userId, 15);
            $user = User::find($userId);
            $this->refreshData();

            Notification::make()
                ->title('Asiento Reservado')
                ->body("Asiento {$assignment->seat->row}.{$assignment->seat->column} reservado para {$user->name}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo reservar el asiento: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function releaseSeat($assignmentId)
    {
        try {
            $assignment = ClassScheduleSeat::findOrFail($assignmentId);
            $assignment->release();
            $this->refreshData();

            Notification::make()
                ->title('Asiento Liberado')
                ->body("Asiento {$assignment->seat->row}.{$assignment->seat->column} liberado exitosamente")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo liberar el asiento')
                ->danger()
                ->send();
        }
    }

    public function confirmSeat($assignmentId)
    {
        try {
            $assignment = ClassScheduleSeat::findOrFail($assignmentId);
            $assignment->confirm();
            $this->refreshData();

            Notification::make()
                ->title('Reserva Confirmada')
                ->body("Asiento {$assignment->seat->row}.{$assignment->seat->column} confirmado exitosamente")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo confirmar la reserva')
                ->danger()
                ->send();
        }
    }

    public function blockSeat($assignmentId)
    {
        try {
            $assignment = ClassScheduleSeat::findOrFail($assignmentId);
            $assignment->block();
            $this->refreshData();

            Notification::make()
                ->title('Asiento Bloqueado')
                ->body("Asiento {$assignment->seat->row}.{$assignment->seat->column} bloqueado")
                ->warning()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo bloquear el asiento')
                ->danger()
                ->send();
        }
    }

    public function unblockSeat($assignmentId)
    {
        try {
            $assignment = ClassScheduleSeat::findOrFail($assignmentId);
            $assignment->unblock();
            $this->refreshData();

            Notification::make()
                ->title('Asiento Desbloqueado')
                ->body("Asiento {$assignment->seat->row}.{$assignment->seat->column} desbloqueado y disponible")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo desbloquear el asiento')
                ->danger()
                ->send();
        }
    }

    public function releaseExpiredReservations()
    {
        try {
            $count = $this->schedule->releaseExpiredReservations();
            $this->refreshData();

            Notification::make()
                ->title('Reservas Expiradas Liberadas')
                ->body("Se liberaron {$count} reservas expiradas")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudieron liberar las reservas expiradas')
                ->danger()
                ->send();
        }
    }

    public function generateSeats()
    {
        try {
            $created = $this->schedule->generateSeatsAutomatically();
            $this->refreshData();

            Notification::make()
                ->title('Asientos Generados')
                ->body("Se generaron {$created} asientos automáticamente")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudieron generar los asientos')
                ->danger()
                ->send();
        }
    }

    public function refreshData()
    {
        $this->loadSeatMap();
        $this->loadReservationStats();
        $this->loadWaitingListStats();
    }

    public function openReassignModal($assignmentId)
    {
        $assignment = ClassScheduleSeat::findOrFail($assignmentId);
        if ($assignment->status !== 'reserved') {
            Notification::make()
                ->title('Error')
                ->body('Solo puedes reasignar asientos reservados')
                ->danger()
                ->send();
            return;
        }
        // Cargar usuarios de lista de espera válidos
        $waitingUsers = $this->schedule->waitingUsers()
            ->whereIn('status', ['waiting', 'notified'])
            ->with(['user', 'userPackage.package'])
            ->orderBy('created_at', 'asc')
            ->get();
        $this->waitingListForReassign = $waitingUsers->map(function($w) {
            return [
                'id' => $w->id,
                'user_id' => $w->user_id,
                'name' => $w->user->name ?? 'Usuario',
                'package' => $w->userPackage->package->name ?? 'Sin paquete',
                'remaining' => $w->userPackage->remaining_classes ?? 0,
                'status' => $w->status,
            ];
        })->toArray();
        $this->reassignAssignmentId = $assignmentId;
        $this->selectedWaitingUserId = null;
        $this->showReassignModal = true;
    }

    public function reassignSeatToWaitingUser()
    {
        if (!$this->reassignAssignmentId || !$this->selectedWaitingUserId) {
            Notification::make()
                ->title('Error')
                ->body('Debes seleccionar un usuario de la lista de espera')
                ->danger()
                ->send();
            return;
        }
        $assignment = ClassScheduleSeat::findOrFail($this->reassignAssignmentId);
        $waitingUser = WaitingClass::with(['user', 'userPackage.package'])
            ->where('id', $this->selectedWaitingUserId)
            ->where('class_schedules_id', $this->schedule->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->first();
        if (!$waitingUser) {
            Notification::make()
                ->title('Error')
                ->body('Usuario de lista de espera no válido')
                ->danger()
                ->send();
            return;
        }
        if (($assignment->user_id === $waitingUser->user_id) || ($assignment->user_waiting_id === $waitingUser->user_id)) {
            Notification::make()
                ->title('Error de Asignación')
                ->body('El usuario ya está asignado a esta butaca.')
                ->danger()
                ->send();
            return;
        }
        // Validar paquete disponible
        $packageService = new \App\Services\PackageValidationService();
        $validation = $packageService->validateUserPackagesForSchedule($this->schedule, $waitingUser->user_id);
        if (!$validation['valid']) {
            Notification::make()
                ->title('Error')
                ->body('El usuario no tiene paquetes válidos: ' . $validation['message'])
                ->danger()
                ->send();
            return;
        }
        $disciplineId = $this->schedule->class->discipline_id;
        $availablePackages = $packageService->getUserAvailablePackagesForDiscipline($waitingUser->user_id, $disciplineId);
        if ($availablePackages->isEmpty()) {
            Notification::make()
                ->title('Error')
                ->body('El usuario no tiene paquetes disponibles para la disciplina')
                ->danger()
                ->send();
            return;
        }
        $bestPackage = $availablePackages->sortBy('expiry_date')->first();
        try {
            \DB::transaction(function () use ($assignment, $waitingUser, $bestPackage) {
                // Si el asiento tenía un user_package_id anterior, devolver clase
                if ($assignment->user_id && $assignment->user_package_id) {
                    $previousUserPackage = \App\Models\UserPackage::find($assignment->user_package_id);
                    if ($previousUserPackage) {
                        $previousUserPackage->remaining_classes += 1;
                        $previousUserPackage->save();
                    }
                }
                $bestPackage->useClasses(1);
                $assignment->update([
                    // 'user_id' => $waitingUser->user_id, // NO modificar user_id
                    'user_waiting_id' => $waitingUser->user_id,
                    'status' => 'reserved',
                    'reserved_at' => now(),
                    'expires_at' => now()->addMinutes(15),
                    'user_package_id' => $bestPackage->id
                ]);
                $waitingUser->update([
                    'status' => 'confirmed',
                    'user_package_id' => $bestPackage->id
                ]);
            });
            $this->showReassignModal = false;
            $this->reassignAssignmentId = null;
            $this->selectedWaitingUserId = null;
            $this->refreshData();
            Notification::make()
                ->title('Asiento reasignado')
                ->body('El asiento fue reasignado exitosamente a la lista de espera')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo reasignar el asiento: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.schedule-seat-map-component');
    }
}
