<?php

namespace App\Livewire;

use App\Models\Studio;
use App\Models\Seat;
use Livewire\Component;
use Filament\Notifications\Notification;

class SeatMapComponent extends Component
{
    public Studio $studio;
    public $seats;
    public $rows;
    public $columns;
    public $addressing;

    public function mount(Studio $studio)
    {
        $this->studio = $studio;
        $this->loadSeats();
    }

    public function loadSeats()
    {
        $this->seats = $this->studio->seats()->get()->keyBy(function ($seat) {
            return $seat->row . '-' . $seat->column;
        });
        $this->rows = $this->studio->row ?? 0;
        $this->columns = $this->studio->column ?? 0;
        $this->addressing = $this->studio->addressing ?? 'left_to_right';
    }

    public function toggleSeat($seatId)
    {
        try {
            $seat = Seat::findOrFail($seatId);
            $seat->update(['is_active' => !$seat->is_active]);

            $this->loadSeats();

            Notification::make()
                ->title('Asiento actualizado')
                ->body("Asiento {$seat->row}.{$seat->column} " . ($seat->is_active ? 'activado' : 'desactivado'))
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo actualizar el asiento')
                ->danger()
                ->send();
        }
    }

    public function createSeat($row, $column)
    {
        try {
            // Validar que no exceda los límites
            if ($row > $this->rows || $column > $this->columns) {
                Notification::make()
                    ->title('Error de validación')
                    ->body("La posición excede los límites de la sala ({$this->rows}×{$this->columns})")
                    ->warning()
                    ->send();
                return;
            }

            // Validar que no exista ya
            $exists = $this->studio->seats()
                ->where('row', $row)
                ->where('column', $column)
                ->exists();

            if ($exists) {
                Notification::make()
                    ->title('Error de validación')
                    ->body("Ya existe un asiento en la posición {$row}.{$column}")
                    ->warning()
                    ->send();
                return;
            }

            // Crear el asiento
            Seat::create([
                'studio_id' => $this->studio->id,
                'row' => $row,
                'column' => $column,
                'is_active' => true,
            ]);

            $this->loadSeats();

            Notification::make()
                ->title('Asiento creado')
                ->body("Nuevo asiento creado en posición {$row}.{$column}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo crear el asiento: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteSeat($seatId)
    {
        try {
            $seat = Seat::findOrFail($seatId);
            $position = $seat->row . '.' . $seat->column;
            $seat->delete();

            $this->loadSeats();

            Notification::make()
                ->title('Asiento eliminado')
                ->body("Asiento {$position} eliminado correctamente")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo eliminar el asiento')
                ->danger()
                ->send();
        }
    }

    public function regenerateSeats()
    {
        try {
            $this->studio->generateSeats();
            $this->loadSeats();

            $totalSeats = $this->seats->count();

            Notification::make()
                ->title('Asientos regenerados')
                ->body("Se generaron {$totalSeats} asientos automáticamente")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudieron regenerar los asientos')
                ->danger()
                ->send();
        }
    }

    public function testAddressing()
    {
        try {
            $preview = $this->studio->getAddressingPreview();
            $positions = array_map(fn($item) => "Asiento {$item['order']} → Col {$item['position']}", $preview);

            Notification::make()
                ->title('Vista previa del direccionamiento')
                ->body('Orden: ' . implode(', ', $positions))
                ->info()
                ->duration(10000)
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error en vista previa')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getStats()
    {
        return [
            'total' => $this->seats->count(),
            'active' => $this->seats->where('is_active', true)->count(),
            'inactive' => $this->seats->where('is_active', false)->count(),
            'capacity' => $this->studio->capacity_per_seat ?? 0,
        ];
    }

    public function render()
    {
        return view('livewire.seat-map-component', [
            'stats' => $this->getStats(),
        ]);
    }
}
