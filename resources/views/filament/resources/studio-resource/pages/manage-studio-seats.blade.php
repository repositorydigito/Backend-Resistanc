<x-filament-panels::page>
    {{-- Componente Livewire para gestión de asientos del estudio --}}
    @livewire('studio-seat-preview', ['studioId' => $this->record->id], key('studio-seats-' . $this->record->id))

    {{-- Información adicional del estudio --}}
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            📊 Información del Estudio
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Configuración básica --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Configuración Básica</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Nombre:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $this->record->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Tipo:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($this->record->studio_type ?? 'N/A') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Ubicación:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $this->record->location ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Estado:</span>
                        <span class="font-medium {{ $this->record->is_active ? 'text-green-600' : 'text-red-600' }}">
                            {{ $this->record->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Configuración de asientos --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Configuración de Asientos</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Filas:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $this->record->row ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Columnas:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $this->record->column ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Capacidad Máxima:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $this->record->max_capacity ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Direccionamiento:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $this->record->addressing ?? 'left_to_right')) }}</span>
                    </div>
                </div>
            </div>

            {{-- Estadísticas de asientos --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Estadísticas de Asientos</h4>
                <div class="space-y-2 text-sm">
                    @php
                        $totalSeats = $this->record->seats()->count();
                        $activeSeats = $this->record->seats()->where('is_active', true)->count();
                        $inactiveSeats = $totalSeats - $activeSeats;
                        $totalPositions = ($this->record->row ?? 0) * ($this->record->column ?? 0);
                        $emptyPositions = $totalPositions - $totalSeats;
                    @endphp
                    
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Total Asientos:</span>
                        <span class="font-medium text-blue-600 dark:text-blue-400">{{ $totalSeats }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Asientos Activos:</span>
                        <span class="font-medium text-green-600 dark:text-green-400">{{ $activeSeats }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Asientos Inactivos:</span>
                        <span class="font-medium text-orange-600 dark:text-orange-400">{{ $inactiveSeats }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Posiciones Vacías:</span>
                        <span class="font-medium text-gray-600 dark:text-gray-400">{{ $emptyPositions }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Acciones rápidas --}}
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-3">🚀 Acciones Rápidas</h4>
        <div class="flex flex-wrap gap-3">
            <a href="{{ \App\Filament\Resources\StudioResource::getUrl('edit', ['record' => $this->record]) }}" 
               class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded-md transition-colors">
                🔧 Editar Configuración
            </a>
            
            @if($this->record->seats()->count() === 0)
                <button 
                    wire:click="regenerateSeats"
                    class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-sm rounded-md transition-colors">
                    ➕ Generar Asientos Iniciales
                </button>
            @endif
            
            <a href="{{ \App\Filament\Resources\ClassScheduleResource::getUrl('index') }}?tableFilters[studio_id][value]={{ $this->record->id }}" 
               class="inline-flex items-center px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white text-sm rounded-md transition-colors">
                📅 Ver Horarios de esta Sala
            </a>
        </div>
    </div>

    {{-- Instrucciones de uso --}}
    <div class="mt-6 bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">💡 Instrucciones de Uso</h4>
        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
            <p>• <strong>Asientos Verdes:</strong> Asientos activos y disponibles para reservas</p>
            <p>• <strong>Asientos Naranjas:</strong> Asientos inactivos (no disponibles para reservas)</p>
            <p>• <strong>Posiciones Grises:</strong> Espacios vacíos donde se pueden crear nuevos asientos</p>
            <p>• <strong>Regenerar Asientos:</strong> Elimina todos los asientos existentes y crea nuevos basados en la configuración actual</p>
            <p>• <strong>Editar Configuración:</strong> Modifica filas, columnas, capacidad y otros parámetros del estudio</p>
        </div>
    </div>
</x-filament-panels::page>
