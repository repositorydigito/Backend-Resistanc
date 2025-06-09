<?php

namespace App\Filament\Resources\StudioResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Contracts\View\View;

class SeatMapDirectRelationManager extends RelationManager
{
    protected static string $relationship = 'seats';

    protected static ?string $title = 'Mapa de Asientos';

    protected static ?string $modelLabel = 'Asiento';

    protected static ?string $pluralModelLabel = 'Asientos';

    protected static ?string $icon = 'heroicon-o-squares-2x2';

    public function render(): View
    {
        return view('filament.resources.studio-resource.seat-map-direct', [
            'studio' => $this->getOwnerRecord(),
            'relationManager' => $this,
        ]);
    }
}
