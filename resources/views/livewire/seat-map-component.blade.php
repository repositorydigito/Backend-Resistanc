<div class="seat-map-container dark:bg-gray-800">
    <style>
        .seat-map-container {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
        }

        .seat-map-header {
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            border-radius: 0.5rem;
            font-weight: bold;
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
        }

        .control-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-regenerate {
            background: #f59e0b;
            color: white;
        }

        .stats-bar {
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

        .addressing-info {
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.5rem;
            color: #1e40af;
            font-size: 0.875rem;
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
            overflow: hidden;
            overflow: scroll;
        }

        .seat {
            width: 45px;
            height: 45px;
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
            transform: scale(1.15);
            z-index: 10;
        }

        .seat-actions {
            position: absolute;
            top: -34px;
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
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            transition: all 0.2s;
        }

        .btn-toggle {
            background: #3b82f6;
            color: white;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete.disabled {
            background: #9ca3af;
            color: #6b7280;
            cursor: not-allowed;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .action-btn.disabled:hover {
            transform: none;
        }

        .seat.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-color: #047857;
        }

        .seat.inactive {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border-color: #b45309;
        }

        .seat.in-use {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border-color: #6d28d9;
            position: relative;
        }

        .seat.in-use::after {
            content: "🔒";
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 0.75rem;
            background: #ef4444;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid white;
        }

        .seat.empty {
            background: #f9fafb;
            color: #9ca3af;
            border: 2px dashed #d1d5db;
            font-size: 1.25rem;
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
            width: 45px;
            height: 45px;
            border: 1px solid #e5e7eb;
        }

        .legend {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .legend-color {
            width: 24px;
            height: 24px;
            border-radius: 0.375rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
    </style>

    @if ($rows > 0 && $columns > 0)
        <div class="seat-map-header"> {{ $studio->name }} - Mapa Interactivo de Asientos</div>

        {{-- <div class="controls-bar">
            <button class="control-btn btn-regenerate" wire:click="regenerateSeats" wire:confirm="¿Estás seguro? Esto eliminará todos los asientos existentes y creará nuevos.">
                🔄 Regenerar Asientos
            </button>
            <button class="control-btn" style="background: #6366f1; color: white;" wire:click="testAddressing">
                🔍 Probar Direccionamiento
            </button>
        </div> --}}

        <div class="stats-bar">
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #059669;">{{ $stats['active'] }}</div>
                <div class="stat-label text-black dark:text-white">Activos</div>
            </div>
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #d97706;">{{ $stats['inactive'] }}</div>
                <div class="stat-label text-black dark:text-white">Inactivos</div>
            </div>
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #374151;">{{ $stats['total'] }}</div>
                <div class="stat-label text-black dark:text-white">Total</div>
            </div>
            <div class="stat-card dark:bg-gray-800">
                <div class="stat-number" style="color: #6366f1;">{{ $stats['capacity'] }}</div>
                <div class="stat-label text-black dark:text-white">Capacidad</div>
            </div>
        </div>

        <div class="addressing-info">
            📍 Direccionamiento:
            @if ($addressing === 'left_to_right')
                Izquierda a Derecha (1 → {{ $columns }})
            @elseif($addressing === 'right_to_left')
                Derecha a Izquierda ({{ $columns }} → 1)
            @elseif($addressing === 'center')
                Desde el Centro
            @else
                {{ $addressing }}
            @endif
        </div>

        <div class="seat-map-grid dark:bg-gray-800" style="grid-template-columns: auto repeat({{ $columns }}, 1fr);">
            <div></div>
            @for ($col = 1; $col <= $columns; $col++)
                <div class="column-label">{{ $col }}</div>
            @endfor

            @for ($row = 1; $row <= $rows; $row++)
                <div class="row-label">{{ $row }}</div>
                @for ($col = 1; $col <= $columns; $col++)
                    @php
                        $seatKey = $row . '-' . $col;
                        $seat = $seats->get($seatKey);
                        $isInUse = $seat ? $this->isSeatInUse($seat) : false;
                        $usageInfo = $seat && $isInUse ? $this->getSeatUsageInfo($seat) : null;
                    @endphp
                    @if ($seat)
                        <div class="seat {{ $seat->is_active ? 'active' : 'inactive' }} {{ $isInUse ? 'in-use' : '' }}"
                            title="Fila {{ $row }}, Columna {{ $col }} - {{ $seat->is_active ? 'Activo' : 'Inactivo' }}{{ $isInUse ? ' - Asignado a horarios de clase' : '' }}">
                            <div class="seat-actions">
                                <button class="action-btn btn-toggle" wire:click="toggleSeat({{ $seat->id }})"
                                    title="{{ $seat->is_active ? 'Desactivar' : 'Activar' }}">
                                    {{ $seat->is_active ? '⏸️' : '▶️' }}
                                </button>
                                @if ($isInUse)
                                    <button class="action-btn btn-delete disabled"
                                        title="No se puede eliminar - Asignado a horarios ({{ $usageInfo['total_assignments'] }} asignaciones, {{ $usageInfo['upcoming_classes'] }} futuras)">
                                        🔒
                                    </button>
                                @else
                                    <button class="action-btn btn-delete" wire:click="deleteSeat({{ $seat->id }})"
                                        wire:confirm="¿Eliminar este asiento permanentemente?" title="Eliminar asiento">
                                        🗑️
                                    </button>
                                @endif
                            </div>
                            {{ $row }}.{{ $col }}
                        </div>
                    @else
                        <div class="seat empty"
                            title="Posición vacía - Fila {{ $row }}, Columna {{ $col }}&#10;• Clic: Crear asiento"
                            wire:click="createSeat({{ $row }}, {{ $col }})">+</div>
                    @endif
                @endfor
            @endfor
        </div>

        <div class="legend dark:bg-gray-800">
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"></div>
                <span>Asiento Activo</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"></div>
                <span>Asiento Inactivo</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); position: relative;">
                    <span style="position: absolute; top: -8px; right: -8px; font-size: 0.75rem; background: #ef4444; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; border: 1px solid white;">🔒</span>
                </div>
                <span>Asignado a horarios (No se puede eliminar)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #f9fafb; border: 2px dashed #d1d5db;"></div>
                <span>Posición Vacía (Clic para crear)</span>
            </div>
        </div>

        <div
            style="text-align: center; margin-top: 1rem; padding: 1rem; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 0.5rem; color: #0c4a6e; font-size: 0.875rem;">
            <strong>💡 Instrucciones:</strong><br>
            • <strong>Posición vacía (+)</strong>: Clic para crear asiento<br>
            • <strong>Asiento existente</strong>: Hover para ver botones de acción<br>
            • <strong>▶️/⏸️</strong>: Activar/Desactivar asiento<br>
            • <strong>🗑️</strong>: Eliminar asiento permanentemente<br>
            • <strong>🔒</strong>: Asiento asignado a horarios (no se puede eliminar)
        </div>
    @else
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🪑</div>
            <h3 style="margin: 0 0 1rem 0; color: #374151; font-size: 1.5rem;">No hay configuración de asientos</h3>
            <p style="margin: 0; font-size: 1rem;">Configure las filas y columnas en la sala para ver el mapa de
                asientos.</p>
        </div>
    @endif
</div>
