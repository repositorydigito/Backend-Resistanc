<div class="schedule-seat-map-container dark:bg-gray-800 ">
    <style>
        .schedule-seat-map-container {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
        }

        .schedule-header {
            text-align: center;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            font-weight: bold;

        }

        .schedule-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-card {
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }

        .info-title {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.125rem;
            font-weight: bold;
            color: #374151;
        }

        .controls-bar {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            flex-wrap: wrap;
        }

        .control-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .btn-generate {
            background: #10b981;
            color: white;
        }

        .btn-release {
            background: #f59e0b;
            color: white;
        }

        .btn-refresh {
            background: #3b82f6;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .seat-map-grid {
            display: grid;
            gap: 0.55rem;
            /* justify-content: center; */
            margin: 1rem 0;
            padding: 1.5rem;
            background: white;
            border-radius: 0.75rem;
            border: 2px solid #e5e7eb;
        }

        .seat {
            width: 50px;
            height: 50px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
        }

        .seat:hover {
            transform: scale(1.1);
            z-index: 10;
        }

        .seat.available {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-color: #047857;
        }

        .seat.reserved {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border-color: #b45309;
        }

        .seat.occupied {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border-color: #b91c1c;
        }

        .seat.completed {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-color: #1d4ed8;
        }

        .seat.blocked {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            border-color: #374151;
        }

        .seat.expired {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            border-color: #c2410c;
            animation: pulse 2s infinite;
        }

        .seat.empty {
            background: #f9fafb;
            color: #9ca3af;
            border: 2px dashed #d1d5db;
            font-size: 1.25rem;
        }

        .seat-actions {
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            display: none;
            background: white;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 0.25rem;
            gap: 0.25rem;
            z-index: 20;
        }

        .seat:hover .seat-actions {
            display: flex;
        }

        .action-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            transition: all 0.2s;
        }

        .btn-reserve {
            background: #10b981;
            color: white;
        }

        .btn-confirm {
            background: #3b82f6;
            color: white;
        }

        .btn-release-action {
            background: #f59e0b;
            color: white;
        }

        .btn-block {
            background: #6b7280;
            color: white;
        }

        .btn-unblock {
            background: #10b981;
            color: white;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .row-label,
        .column-label {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #374151;
            background: #f9fafb;
            border-radius: 0.375rem;
            width: 50px;
            height: 50px;
            border: 1px solid #e5e7eb;
        }

        .legend {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 0.25rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .stage-indicator {
            text-align: center;
            margin-bottom: 1rem;
        }

        .stage-label {
            display: inline-block;
            background: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            color: #374151;
            font-weight: 600;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* Estilos para el modal */
        .modal-overlay {
            backdrop-filter: blur(4px);
        }

        .user-item {
            transition: all 0.2s ease;
        }

        .user-item:hover {
            transform: translateX(4px);
        }

        .user-item.selected {
            border-left: 4px solid #3b82f6;
        }
    </style>

    @if ($rows > 0 && $columns > 0)
        <div class="schedule-header dark:bg-gray-800 ">
            🎭 {{ $schedule->class->name ?? 'Clase' }} - {{ $studioInfo['name'] }}
            <div class="text-black dark:text-white" style="font-size: 0.875rem; margin-top: 0.5rem; opacity: 0.9;">
                📅 {{ $schedule->scheduled_date->format('d/m/Y') }} •
                ⏰ {{ $schedule->start_time }} - {{ $schedule->end_time }}
            </div>
        </div>

        <div class="schedule-info">
            <div class="info-card dark:bg-gray-800">
                <div class="info-title dark:text-white">👨‍🏫 Instructor</div>
                <div class="info-value dark:text-white">{{ $schedule->instructor->name ?? 'Sin asignar' }}</div>
            </div>
            <div class="info-card dark:bg-gray-800">
                <div class="info-title dark:text-white">👥 Capacidad Máxima</div>
                <div class="info-value dark:text-white">{{ $schedule->max_capacity }}</div>
            </div>
            <div class="info-card dark:bg-gray-800">
                <div class="info-title dark:text-white">📊 Estado</div>
                <div class="info-value dark:text-white">
                    @php
                        switch ($schedule->status) {
                            case 'scheduled':
                                echo 'Programado';
                                break;
                            case 'in_progress':
                                echo 'En Progreso';
                                break;
                            case 'completed':
                                echo 'Completado';
                                break;
                            case 'cancelled':
                                echo 'Cancelado';
                                break;
                            case 'postponed':
                                echo 'Pospuesto';
                                break;
                            default:
                                echo ucfirst($schedule->status);
                        }
                    @endphp

                </div>
            </div>
            {{-- <div class="info-card">
                <div class="info-title">💰 Precio</div>
                <div class="info-value">${{ number_format($schedule->price_per_class ?? 0, 2) }}</div>
            </div> --}}
        </div>

        {{-- <div class="controls-bar dark:bg-gray-800">
            <button class="control-btn btn-generate " wire:click="generateSeats"
                wire:confirm="¿Generar asientos automáticamente? Esto creará asientos para todas las posiciones activas del estudio.">
                🪑 Generar Asientos
            </button>
            <button class="control-btn btn-release" wire:click="releaseExpiredReservations"
                wire:confirm="¿Liberar todas las reservas expiradas?">
                🕒 Liberar Expirados ({{ $reservationStats['expired'] }})
            </button>
            <button class="control-btn btn-refresh" wire:click="refreshData">
                🔄 Actualizar
            </button>
        </div> --}}

        <div class="stats-grid dark:bg-gray-800">
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #059669;">{{ $reservationStats['available'] }}</div>
                <div class="text-black dark:text-white">Disponibles</div>
            </div>
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #d97706;">{{ $reservationStats['reserved'] }}</div>
                <div class="text-black dark:text-white">Reservados</div>
            </div>
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #dc2626;">{{ $reservationStats['occupied'] }}</div>
                <div class="text-black dark:text-white">Ocupados</div>
            </div>
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #2563eb;">{{ $reservationStats['completed'] }}</div>
                <div class="text-black dark:text-white">Completados</div>
            </div>
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #4b5563;">{{ $reservationStats['blocked'] }}</div>
                <div class="text-black dark:text-white">Bloqueados</div>
            </div>
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #374151;">{{ $reservationStats['total_seats'] }}</div>
                <div class="text-black dark:text-white">Total</div>
            </div>
        </div>

        {{-- <div class="stage-indicator">
            <div class="stage-label dark:text-white">Sala</div>
        </div> --}}

        <div class="seat-map-grid dark:bg-gray-800 overflow-auto w-full"
            style="grid-template-columns: auto repeat({{ $columns }}, 1fr);">
            <div></div>
            @for ($col = 1; $col <= $columns; $col++)
                <div class="column-label">{{ $col }}</div>
            @endfor

            @for ($row = 1; $row <= $rows; $row++)
                <div class="row-label">{{ $row }}</div>
                @for ($col = 1; $col <= $columns; $col++)
                    @php
                        $seat = $seatMap[$row][$col] ?? null;
                        $seatClasses = 'seat';

                        if (!$seat || !$seat['exists']) {
                            $seatClasses .= ' empty';
                            $seatContent = 'X';
                        } else {
                            $status = $seat['status'];
                            if ($seat['is_expired']) {
                                $seatClasses .= ' expired';
                            } else {
                                $seatClasses .= ' ' . $status;
                            }
                            $seatContent = $seat['seat_identifier'] ?? "{$row}.{$col}";
                        }
                    @endphp

                    <div class="{{ $seatClasses }}"
                        @if ($seat && $seat['exists']) title="Asiento: {{ $seat['seat_identifier'] }}
Estado: {{ ucfirst($seat['status']) }}
@if ($seat['user_name'])Usuario: {{ $seat['user_name'] }} ({{ $seat['user_email'] }}) @endif
                        @if ($seat['reserved_at']) Reservado: {{ \Carbon\Carbon::parse($seat['reserved_at'])->format('d/m/Y H:i') }} @endif
                        @if ($seat['expires_at']) Expira: {{ \Carbon\Carbon::parse($seat['expires_at'])->format('d/m/Y H:i') }} @endif"
                        @endif>

                        @if ($seat && $seat['exists'])
                            <div class="seat-actions">
                                @if ($seat['status'] === 'available')
                                    <button class="action-btn btn-reserve"
                                        wire:click="openReservationModal({{ $seat['assignment_id'] }})"
                                        title="Reservar">📝</button>
                                @endif

                                @if ($seat['status'] === 'reserved')
                                    <button class="action-btn btn-confirm"
                                        wire:click="confirmSeat({{ $seat['assignment_id'] }})"
                                        title="Confirmar">✅</button>
                                @endif

                                @if (in_array($seat['status'], ['reserved', 'occupied']))
                                    <button class="action-btn btn-release-action"
                                        wire:click="releaseSeat({{ $seat['assignment_id'] }})"
                                        wire:confirm="¿Liberar este asiento?" title="Liberar">🔓</button>
                                @endif

                                @if ($seat['status'] === 'blocked')
                                    <button class="action-btn btn-unblock"
                                        wire:click="unblockSeat({{ $seat['assignment_id'] }})"
                                        wire:confirm="¿Desbloquear este asiento?" title="Desbloquear">🔓</button>
                                @else
                                    <button class="action-btn btn-block"
                                        wire:click="blockSeat({{ $seat['assignment_id'] }})"
                                        wire:confirm="¿Bloquear este asiento?" title="Bloquear">🚫</button>
                                @endif
                            </div>
                        @endif

                        {{ $seatContent }}
                    </div>
                @endfor
            @endfor
        </div>

        <div class="legend dark:bg-gray-800">
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"></div>
                <span>Disponible</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"></div>
                <span>Reservado</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);"></div>
                <span>Ocupado</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);"></div>
                <span>Completado</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);"></div>
                <span>Bloqueado</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);"></div>
                <span>Expirado</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #f9fafb; border: 2px dashed #d1d5db;"></div>
                <span>Sin asiento</span>
            </div>
        </div>

        <div
            style="text-align: center; margin-top: 1rem; padding: 1rem; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 0.5rem; color: #0c4a6e; font-size: 0.875rem;">
            <strong>💡 Instrucciones:</strong><br>
            • <strong>Hover sobre asientos</strong>: Ver acciones disponibles<br>
            • <strong>📝</strong>: Reservar asiento (seleccionar usuario)<br>
            • <strong>✅</strong>: Confirmar reserva<br>
            • <strong>🔓</strong>: Liberar/Desbloquear asiento<br>
            • <strong>🚫</strong>: Bloquear asiento
        </div>
    @else
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🪑</div>
            <h3 style="margin: 0 0 1rem 0; color: #374151; font-size: 1.5rem;">No hay configuración de asientos</h3>
            <p style="margin: 0; font-size: 1rem;">El estudio no tiene configuración de filas y columnas.</p>
            <button class="control-btn btn-generate" wire:click="generateSeats" style="margin-top: 1rem;">
                🪑 Generar Asientos Automáticamente
            </button>
        </div>
    @endif

    {{-- Modal de Selección de Usuario --}}
    @if ($showUserModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 modal-overlay"
            wire:click="closeModal">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4" wire:click.stop>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            🪑 Reservar Asiento
                        </h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        {{-- Búsqueda de Usuario --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                👤 Buscar Usuario
                            </label>
                            <input type="text" wire:model.live="userSearch"
                                placeholder="Buscar clientes por nombre o email..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        {{-- Lista de Usuarios --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Seleccionar Usuario
                            </label>
                            <div
                                class="max-h-48 overflow-y-auto border border-gray-300 rounded-md dark:border-gray-600">
                                @forelse($availableUsers as $user)
                                    <div wire:click="$set('selectedUserId', {{ $user['id'] }})"
                                        class="user-item p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-600 {{ $selectedUserId == $user['id'] ? 'bg-blue-50 dark:bg-blue-900 selected' : '' }}">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $user['name'] }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user['email'] }}
                                        </div>
                                        <div class="text-xs text-blue-600 dark:text-blue-400">👤 {{ $user['roles'] }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                                        @if (empty($userSearch))
                                            Escribe para buscar clientes...
                                        @else
                                            No se encontraron clientes con ese criterio
                                        @endif
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Tiempo de Reserva --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ⏰ Tiempo de Reserva (minutos)
                            </label>
                            <select wire:model="reservationMinutes"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="15">15 minutos</option>
                                <option value="30">30 minutos</option>
                                <option value="60">1 hora</option>
                                <option value="120">2 horas</option>
                            </select>
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end space-x-3 mt-6">
                        <button wire:click="closeModal"
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Cancelar
                        </button>
                        <button wire:click="reserveSeat"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
                            {{ !$selectedUserId ? 'disabled' : '' }}>
                            🪑 Reservar Asiento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- JavaScript para auto-actualización --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Escuchar evento de actualización del horario
            window.addEventListener('schedule-updated', function(event) {
                console.log('Schedule updated in seat map component', event.detail);
                @this.call('refreshSeatMap');
            });

            // Auto-refresh cada 15 segundos para reservas en tiempo real
            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    @this.call('refreshSeatMap');
                }
            }, 15000);
        });
    </script>
</div>
