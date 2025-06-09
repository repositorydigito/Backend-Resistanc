<x-filament-panels::page>
    {{-- Estadísticas de Reservas --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $reservationStats['total_seats'] }}</div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 shadow">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">Disponibles</div>
            <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $reservationStats['available'] }}</div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 shadow">
            <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Reservados</div>
            <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $reservationStats['reserved'] }}</div>
        </div>

        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 shadow">
            <div class="text-sm font-medium text-red-600 dark:text-red-400">Ocupados</div>
            <div class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $reservationStats['occupied'] }}</div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 shadow">
            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Completados</div>
            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $reservationStats['completed'] }}</div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 shadow">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Bloqueados</div>
            <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $reservationStats['blocked'] }}</div>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 shadow">
            <div class="text-sm font-medium text-orange-600 dark:text-orange-400">Expirados</div>
            <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">{{ $reservationStats['expired'] }}</div>
        </div>
    </div>

    {{-- Información del Estudio --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            📍 {{ $studioInfo['name'] }}
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Capacidad:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $studioInfo['capacity'] }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Filas:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $studioInfo['rows'] }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Columnas:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $studioInfo['columns'] }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Posiciones:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $studioInfo['total_positions'] }}</span>
            </div>
        </div>
    </div>

    {{-- Leyenda --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow mb-6">
        <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">Leyenda</h4>
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-gray-200 dark:bg-gray-600 rounded border-2 border-dashed border-gray-400"></div>
                <span class="text-gray-600 dark:text-gray-400">Sin asiento</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-green-100 dark:bg-green-900 rounded border-2 border-green-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Disponible</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-yellow-100 dark:bg-yellow-900 rounded border-2 border-yellow-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Reservado</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-red-100 dark:bg-red-900 rounded border-2 border-red-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Ocupado</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-blue-100 dark:bg-blue-900 rounded border-2 border-blue-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Completado</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-gray-100 dark:bg-gray-900 rounded border-2 border-gray-500"></div>
                <span class="text-gray-600 dark:text-gray-400">Bloqueado</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-orange-100 dark:bg-orange-900 rounded border-2 border-orange-500 animate-pulse"></div>
                <span class="text-gray-600 dark:text-gray-400">Expirado</span>
            </div>
        </div>
    </div>

    {{-- Mapa de Asientos --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            🪑 Mapa de Asientos
        </h3>

        {{-- Indicador de Frente/Escenario --}}
        <div class="text-center mb-4">
            <div class="inline-block bg-gray-100 dark:bg-gray-700 px-6 py-2 rounded-lg">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">🎭 FRENTE / ESCENARIO</span>
            </div>
        </div>

        {{-- Grid de Asientos --}}
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full">
                @for ($row = 1; $row <= $studioInfo['rows']; $row++)
                    <div class="flex items-center justify-center gap-1 mb-2">
                        {{-- Número de fila --}}
                        <div class="w-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $row }}
                        </div>

                        {{-- Asientos de la fila --}}
                        @for ($col = 1; $col <= $studioInfo['columns']; $col++)
                            @php
                                $seat = $seatMap[$row][$col] ?? null;
                                $seatClasses = 'w-12 h-12 rounded border-2 flex items-center justify-center text-xs font-medium cursor-pointer transition-all duration-200 hover:scale-105';

                                if (!$seat || !$seat['exists']) {
                                    $seatClasses .= ' bg-gray-200 dark:bg-gray-600 border-dashed border-gray-400';
                                    $seatContent = '';
                                    $clickAction = '';
                                } else {
                                    $clickAction = '';
                                    switch ($seat['status']) {
                                        case 'available':
                                            $seatClasses .= ' bg-green-100 dark:bg-green-900 border-green-500 text-green-700 dark:text-green-300 hover:bg-green-200';
                                            $clickAction = "wire:click=\"reserveSeat({$seat['assignment_id']})\"";
                                            break;
                                        case 'reserved':
                                            $extraClass = $seat['is_expired'] ? ' animate-pulse bg-orange-100 dark:bg-orange-900 border-orange-500' : ' bg-yellow-100 dark:bg-yellow-900 border-yellow-500';
                                            $seatClasses .= $extraClass . ' text-yellow-700 dark:text-yellow-300';
                                            break;
                                        case 'occupied':
                                            $seatClasses .= ' bg-red-100 dark:bg-red-900 border-red-500 text-red-700 dark:text-red-300';
                                            break;
                                        case 'Completed':
                                            $seatClasses .= ' bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300';
                                            break;
                                        case 'blocked':
                                            $seatClasses .= ' bg-gray-100 dark:bg-gray-900 border-gray-500 text-gray-700 dark:text-gray-300';
                                            break;
                                        default:
                                            $seatClasses .= ' bg-gray-100 dark:bg-gray-700 border-gray-400';
                                    }
                                    $seatContent = $seat['seat_identifier'] ?? "{$row}.{$col}";
                                }
                            @endphp

                            <div
                                class="{{ $seatClasses }}"
                                @if($clickAction) {{ $clickAction }} @endif
                                @if($seat && $seat['exists'])
                                    title="Asiento: {{ $seat['seat_identifier'] }}
Estado: {{ ucfirst($seat['status']) }}
@if($seat['user_name'])Usuario: {{ $seat['user_name'] }}@endif
@if($seat['reserved_at'])Reservado: {{ \Carbon\Carbon::parse($seat['reserved_at'])->format('d/m/Y H:i') }}@endif
@if($seat['expires_at'])Expira: {{ \Carbon\Carbon::parse($seat['expires_at'])->format('d/m/Y H:i') }}@endif"
                                @endif
                            >
                                {{ $seatContent }}
                            </div>
                        @endfor

                        {{-- Número de fila (derecha) --}}
                        <div class="w-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $row }}
                        </div>
                    </div>
                @endfor

                {{-- Números de columna --}}
                <div class="flex items-center justify-center gap-1 mt-4">
                    <div class="w-8"></div>
                    @for ($col = 1; $col <= $studioInfo['columns']; $col++)
                        <div class="w-12 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $col }}
                        </div>
                    @endfor
                    <div class="w-8"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Acciones Rápidas para Asientos Seleccionados --}}
    @if($reservationStats['reserved'] > 0 || $reservationStats['occupied'] > 0)
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">Acciones Rápidas</h4>
            <div class="flex flex-wrap gap-2">
                @if($reservationStats['reserved'] > 0)
                    <button
                        wire:click="releaseExpiredReservations"
                        class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-medium transition-colors"
                    >
                        🕒 Liberar Expirados ({{ $reservationStats['expired'] }})
                    </button>
                @endif

                <button
                    wire:click="refreshSeatMap"
                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    🔄 Actualizar Mapa
                </button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
