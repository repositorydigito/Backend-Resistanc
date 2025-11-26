<div>
    @if($studio && $rows > 0 && $columns > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    🪑 {{ $studio->name }} - Mapa Interactivo de Asientos
                </h3>
                <div class="flex gap-2">
                    <button 
                        wire:click="loadStudioData"
                        class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded-md transition-colors"
                        title="Actualizar mapa"
                    >
                        🔄 Actualizar
                    </button>
                </div>
            </div>

            {{-- Estadísticas --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $seatStats['active_seats'] ?? 0 }}</div>
                    <div class="text-sm text-green-600 dark:text-green-400">Activos</div>
                </div>
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $seatStats['inactive_seats'] ?? 0 }}</div>
                    <div class="text-sm text-orange-600 dark:text-orange-400">Inactivos</div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $seatStats['total_seats'] ?? 0 }}</div>
                    <div class="text-sm text-blue-600 dark:text-blue-400">Total</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $seatStats['capacity'] ?? 0 }}</div>
                    <div class="text-sm text-purple-600 dark:text-purple-400">Capacidad</div>
                </div>
            </div>

            {{-- Información del direccionamiento --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 mb-4">
                <div class="flex items-center justify-center gap-2 text-sm text-blue-700 dark:text-blue-300">
                    📍 <strong>Direccionamiento:</strong> {{ ucfirst(str_replace('_', ' ', $studio->addressing ?? 'left_to_right')) }}
                </div>
            </div>

            {{-- Indicador de Frente/Escenario --}}
            <div class="text-center mb-4">
                <div class="inline-block bg-gray-100 dark:bg-gray-700 px-6 py-2 rounded-lg">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">🎭 FRENTE / ESCENARIO</span>
                </div>
            </div>

            {{-- Mapa de Asientos --}}
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full">
                    {{-- Números de columna (arriba) --}}
                    <div class="flex items-center justify-center gap-1 mb-2">
                        <div class="w-8"></div>
                        @for ($col = 1; $col <= $columns; $col++)
                            <div class="w-12 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ $col }}
                            </div>
                        @endfor
                        <div class="w-8"></div>
                    </div>

                    {{-- Filas de asientos --}}
                    @for ($row = 1; $row <= $rows; $row++)
                        <div class="flex items-center justify-center gap-1 mb-2">
                            {{-- Número de fila (izquierda) --}}
                            <div class="w-8 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ $row }}
                            </div>

                            {{-- Asientos de la fila --}}
                            @for ($col = 1; $col <= $columns; $col++)
                                @php
                                    $seat = $seats[$row][$col] ?? null;
                                    $seatClasses = 'w-12 h-12 rounded border-2 flex items-center justify-center text-xs font-medium transition-all duration-200';

                                    if (!$seat || !$seat['exists']) {
                                        $seatClasses .= ' bg-gray-200 dark:bg-gray-600 border-dashed border-gray-400';
                                        $seatContent = '+';
                                        $title = "Posición Vacía (Clic para crear)";
                                    } else {
                                        if ($seat['is_active']) {
                                            $seatClasses .= ' bg-green-100 dark:bg-green-900 border-green-500 text-green-700 dark:text-green-300';
                                            $title = "Asiento Activo - ID: {$seat['seat_id']}";
                                        } else {
                                            $seatClasses .= ' bg-orange-100 dark:bg-orange-900 border-orange-500 text-orange-700 dark:text-orange-300';
                                            $title = "Asiento Inactivo - ID: {$seat['seat_id']}";
                                        }
                                        $seatContent = $seat['seat_number'] ?? "{$row}.{$col}";
                                    }
                                @endphp

                                <div
                                    class="{{ $seatClasses }}"
                                    title="{{ $title }}"
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

                    {{-- Números de columna (abajo) --}}
                    <div class="flex items-center justify-center gap-1 mt-2">
                        <div class="w-8"></div>
                        @for ($col = 1; $col <= $columns; $col++)
                            <div class="w-12 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ $col }}
                            </div>
                        @endfor
                        <div class="w-8"></div>
                    </div>
                </div>
            </div>

            {{-- Leyenda --}}
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Leyenda</h4>
                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-green-100 dark:bg-green-900 rounded border-2 border-green-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Asiento Activo</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-orange-100 dark:bg-orange-900 rounded border-2 border-orange-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Asiento Inactivo</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gray-200 dark:bg-gray-600 rounded border-2 border-dashed border-gray-400"></div>
                        <span class="text-gray-600 dark:text-gray-400">Posición Vacía (Clic para crear)</span>
                    </div>
                </div>
            </div>
        </div>
    @elseif($studioId && !$studio)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-700">
            <div class="flex items-center gap-2 text-yellow-700 dark:text-yellow-300">
                ⚠️ <strong>Sala no encontrada</strong>
            </div>
            <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-1">
                La sala seleccionada no existe o no tiene configuración de asientos.
            </p>
        </div>
    @elseif($studioId)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                📋 <strong>Sala sin configurar</strong>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Esta sala no tiene configuración de filas y columnas. Ve a la gestión de salas para configurarla.
            </p>
        </div>
    @else
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                🪑 <strong>Selecciona una sala</strong>
            </div>
            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                Selecciona una sala arriba para ver su mapa de asientos interactivo.
            </p>
        </div>
    @endif
</div>
