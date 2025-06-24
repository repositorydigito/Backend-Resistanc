# Gestión de Asientos en Salas

## Funcionalidades Implementadas

### 1. Prevención de Eliminación de Salas con Horarios Asociados

**Problema:** No se debe permitir eliminar una sala que tiene horarios de clase asociados.

**Solución:**
- Se agregó el método `hasClassSchedules()` al modelo `Studio`
- Se implementó validación en `StudioResource` para prevenir eliminación
- Se muestra información visual en la tabla de salas

**Características:**
- ✅ Botón de eliminar deshabilitado para salas con horarios
- ✅ Mensaje de error explicativo al intentar eliminar
- ✅ Columna "Horarios" que muestra el conteo de horarios asociados
- ✅ Tooltip informativo sobre el estado de la sala
- ✅ Validación en eliminación masiva

### 2. Reordenamiento Automático de Números de Asientos

**Problema:** Los números de asientos deben mantenerse secuenciales (1, 2, 3, 4...) independientemente del orden de creación.

**Solución:**
- Se implementó el método `reorderSeatNumbers()` en el modelo `Studio`
- Se agregaron métodos `addSeat()` y `deleteSeat()` que incluyen reordenamiento automático
- Se creó comando Artisan para reordenamiento manual

**Ejemplo de Funcionamiento:**
```
Estado inicial:
- Asiento 1-2 → Número 1
- Asiento 1-5 → Número 2

Después de agregar asiento 1-3:
- Asiento 1-2 → Número 1
- Asiento 1-3 → Número 2  ← Nuevo
- Asiento 1-5 → Número 3  ← Reordenado
```

## Métodos del Modelo Studio

### `hasClassSchedules(): bool`
Verifica si la sala tiene horarios de clase asociados.

### `getClassSchedulesCountAttribute(): int`
Retorna el número de horarios asociados a la sala.

### `reorderSeatNumbers(): void`
Reordena todos los números de asientos para mantener secuencia correlativa (1, 2, 3, 4...).

### `addSeat(int $row, int $column): Seat`
Agrega un nuevo asiento en la posición especificada y reordena automáticamente los números.

### `deleteSeat(int $seatId): bool`
Elimina un asiento específico y reordena los números restantes. Retorna `false` si el asiento está asignado a horarios.

## Comando Artisan

### `studio:reorder-seats`

**Opciones:**
- `--studio-id=X`: Reordenar asientos de una sala específica
- `--all`: Reordenar asientos de todas las salas
- `--dry-run`: Simular cambios sin aplicarlos

**Ejemplos de uso:**
```bash
# Reordenar una sala específica
php artisan studio:reorder-seats --studio-id=1

# Reordenar todas las salas
php artisan studio:reorder-seats --all

# Simular reordenamiento sin aplicar cambios
php artisan studio:reorder-seats --all --dry-run
```

## Interfaz de Usuario (Filament)

### StudioResource
- **Columna "Horarios":** Muestra el número de horarios asociados con indicador visual
- **Botón Eliminar:** Deshabilitado para salas con horarios, con tooltip explicativo
- **Eliminación Masiva:** Valida que ninguna sala seleccionada tenga horarios

### SeatsRelationManager
- **Acción "Agregar Espacio":** Formulario para agregar nuevo asiento con validación de posición
- **Acción "Reordenar Números":** Botón para reordenar manualmente los números
- **Eliminación de Asientos:** Verifica que el asiento no esté asignado a horarios antes de eliminar

## Flujo de Trabajo Recomendado

### Para Agregar un Nuevo Asiento:
1. Ir a la sala en Filament
2. Navegar a la pestaña "Espacios"
3. Hacer clic en "Agregar Espacio"
4. Especificar fila y columna
5. El sistema automáticamente:
   - Valida que la posición no esté ocupada
   - Crea el nuevo asiento
   - Reordena todos los números
   - Muestra notificación de éxito

### Para Eliminar un Asiento:
1. Verificar que el asiento no esté asignado a horarios (botón deshabilitado si lo está)
2. Hacer clic en "Eliminar"
3. El sistema automáticamente:
   - Elimina el asiento
   - Reordena los números restantes
   - Muestra notificación de éxito

### Para Reordenar Manualmente:
1. Usar el botón "Reordenar Números" en la interfaz
2. O ejecutar el comando Artisan: `php artisan studio:reorder-seats --studio-id=X`

## Logs y Monitoreo

Todos los cambios se registran en los logs de Laravel:
- Creación de asientos
- Reordenamiento de números
- Eliminación de asientos
- Errores de validación

## Consideraciones de Seguridad

- ✅ No se pueden eliminar salas con horarios asociados
- ✅ No se pueden eliminar asientos asignados a horarios
- ✅ Validación de posiciones duplicadas
- ✅ Logs detallados para auditoría
- ✅ Modo simulación disponible en comandos

## Casos de Uso Comunes

### Escenario 1: Sala Nueva
1. Crear sala con configuración de filas/columnas
2. Los asientos se generan automáticamente con números secuenciales

### Escenario 2: Agregar Asiento Intermedio
1. Agregar asiento en posición 1-3
2. Los números se reordenan: 1-2→1, 1-3→2, 1-5→3

### Escenario 3: Eliminar Asiento
1. Eliminar asiento 1-3
2. Los números se reordenan: 1-2→1, 1-5→2

### Escenario 4: Cambiar Configuración de Sala
1. Modificar filas/columnas de la sala
2. Los asientos se regeneran automáticamente con números secuenciales 