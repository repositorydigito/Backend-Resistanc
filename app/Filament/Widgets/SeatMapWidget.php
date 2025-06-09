<?php

namespace App\Filament\Widgets;

use App\Models\Studio;
use Filament\Widgets\Widget;

class SeatMapWidget extends Widget
{
    protected static string $view = 'filament.widgets.seat-map-widget';

    public ?Studio $studio = null;

    protected int | string | array $columnSpan = 'full';

    public function mount(?Studio $studio = null): void
    {
        $this->studio = $studio ?? $this->getWidgetData()['studio'] ?? null;
    }

    protected function getWidgetData(): array
    {
        return $this->widgetData ?? [];
    }
}
