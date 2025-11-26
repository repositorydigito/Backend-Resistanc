<div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-ta-header-ctn divide-y divide-gray-200 dark:divide-white/10">
        <div class="fi-ta-header flex flex-col gap-3 p-4 sm:px-6 sm:flex-row sm:items-center">
            <div class="grid gap-y-1">
                <h3 class="fi-ta-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    🗺️ Mapa Visual de Asientos
                </h3>
                <p class="fi-ta-header-description text-sm text-gray-500 dark:text-gray-400">
                    Gestiona las reservas de asientos de manera visual e interactiva
                </p>
            </div>
        </div>
    </div>

    <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
        <div class="p-6">
            @livewire(\App\Livewire\ScheduleSeatMapComponent::class, ['schedule' => $schedule], key('schedule-seat-map-' . $schedule->id))
        </div>
    </div>
</div>
