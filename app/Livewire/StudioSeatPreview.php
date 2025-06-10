<?php

namespace App\Livewire;

use App\Models\Studio;
use App\Models\Seat;
use Livewire\Component;

class StudioSeatPreview extends Component
{
    public $studioId = null;
    public $studio = null;
    public $seats = [];
    public $rows = 0;
    public $columns = 0;
    public $seatStats = [];

    public function mount($studioId = null)
    {
        if ($studioId) {
            $this->studioId = $studioId;
            $this->loadStudioData();
        }
    }

    public function updatedStudioId($value)
    {
        if ($value) {
            $this->studioId = $value;
            $this->loadStudioData();
        } else {
            $this->resetStudioData();
        }
    }

    public function loadStudioData()
    {
        if (!$this->studioId) {
            $this->resetStudioData();
            return;
        }

        $this->studio = Studio::find($this->studioId);
        
        if (!$this->studio) {
            $this->resetStudioData();
            return;
        }

        $this->rows = $this->studio->row ?? 0;
        $this->columns = $this->studio->column ?? 0;

        // Cargar asientos del estudio
        $studioSeats = $this->studio->seats()->get()->keyBy(function ($seat) {
            return $seat->row . '-' . $seat->column;
        });

        // Inicializar mapa de asientos
        $this->seats = [];
        for ($row = 1; $row <= $this->rows; $row++) {
            for ($col = 1; $col <= $this->columns; $col++) {
                $seatKey = $row . '-' . $col;
                $seat = $studioSeats->get($seatKey);
                
                $this->seats[$row][$col] = [
                    'exists' => $seat ? true : false,
                    'seat_id' => $seat ? $seat->id : null,
                    'is_active' => $seat ? $seat->is_active : false,
                    'seat_number' => $seat ? $seat->seat_number : null,
                ];
            }
        }

        // Calcular estadísticas
        $this->calculateSeatStats();
    }

    public function resetStudioData()
    {
        $this->studio = null;
        $this->seats = [];
        $this->rows = 0;
        $this->columns = 0;
        $this->seatStats = [];
    }

    public function calculateSeatStats()
    {
        if (!$this->studio) {
            $this->seatStats = [];
            return;
        }

        $totalSeats = $this->studio->seats()->count();
        $activeSeats = $this->studio->seats()->where('is_active', true)->count();
        $inactiveSeats = $totalSeats - $activeSeats;
        $totalPositions = $this->rows * $this->columns;
        $emptyPositions = $totalPositions - $totalSeats;

        $this->seatStats = [
            'total_seats' => $totalSeats,
            'active_seats' => $activeSeats,
            'inactive_seats' => $inactiveSeats,
            'empty_positions' => $emptyPositions,
            'total_positions' => $totalPositions,
            'capacity' => $this->studio->capacity_per_seat ?? $this->studio->max_capacity,
        ];
    }

    public function render()
    {
        return view('livewire.studio-seat-preview');
    }
}
