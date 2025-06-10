<?php

namespace App\Filament\Resources\ClassScheduleResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Contracts\View\View;

class SeatMapVisualRelationManager extends RelationManager
{
    protected static string $relationship = 'seatAssignments';

    protected static ?string $title = 'Mapa Visual de Asientos';

    protected static ?string $modelLabel = 'Asiento';

    protected static ?string $pluralModelLabel = 'Asientos';

    protected static ?string $icon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public function render(): View
    {
        return view('filament.resources.class-schedule-resource.relation-managers.seat-map-visual', [
            'schedule' => $this->getOwnerRecord(),
            'relationManager' => $this,
        ]);
    }
}
