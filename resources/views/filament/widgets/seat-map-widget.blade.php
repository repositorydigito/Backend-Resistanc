<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🎭 Mapa Visual de Asientos
        </x-slot>

        <x-slot name="description">
            Gestiona los asientos de manera visual e interactiva
        </x-slot>

        @if($studio)
            @livewire(\App\Livewire\SeatMapComponent::class, ['studio' => $studio], key('seat-map-widget-' . $studio->id))
        @else
            <div class="text-center py-8 text-gray-500">
                <div class="text-4xl mb-4">🪑</div>
                <p>No se pudo cargar el estudio</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
