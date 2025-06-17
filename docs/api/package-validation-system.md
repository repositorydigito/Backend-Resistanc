# Sistema de Validación de Paquetes para Reservas

## Descripción General

El sistema de validación de paquetes asegura que los usuarios solo puedan reservar clases para las cuales tienen paquetes válidos y disponibles. Cada paquete está asociado a una disciplina específica (Yoga, Cycling, etc.) y solo puede ser usado para clases de esa disciplina.

## Flujo de Validación

### 1. Verificación de Paquetes Disponibles

Antes de realizar una reserva, el sistema valida:

- ✅ El usuario tiene paquetes activos
- ✅ Los paquetes corresponden a la disciplina de la clase
- ✅ Los paquetes tienen clases disponibles (remaining_classes > 0)
- ✅ Los paquetes no han expirado

### 2. Consumo Automático de Paquetes

Cuando se confirma una reserva exitosa:

- 🔄 Se consume automáticamente 1 clase del paquete más próximo a expirar
- 📊 Se actualiza `used_classes` (+1) y `remaining_classes` (-1)
- 📝 Se registra la información del paquete consumido en la respuesta

## Endpoints Relacionados

### Verificar Disponibilidad de Paquetes

```http
GET /api/class-schedules/{classSchedule}/check-packages
Authorization: Bearer {token}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Paquetes disponibles encontrados",
  "data": {
    "can_reserve": true,
    "discipline_required": {
      "id": 1,
      "name": "Yoga"
    },
    "available_packages": [
      {
        "id": 42,
        "package_code": "PKG001-2024",
        "package_name": "Paquete Yoga 10 Clases",
        "remaining_classes": 9,
        "expiry_date": "2025-02-15",
        "days_remaining": 35
      }
    ]
  }
}
```

### Reservar Asientos (Con Validación de Paquetes)

```http
POST /api/class-schedules/{classSchedule}/reserve-seats
Authorization: Bearer {token}
Content-Type: application/json

{
  "class_schedule_seat_ids": [267, 268],
  "minutes_to_expire": 15
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Asientos reservados exitosamente",
  "data": {
    "reserved_seats": [...],
    "reservation_summary": {...},
    "package_consumption": {
      "id": 42,
      "package_code": "PKG001-2024",
      "package_name": "Paquete Yoga 10 Clases",
      "classes_consumed": 1,
      "remaining_classes": 8,
      "used_classes": 2
    }
  }
}
```

**Error por paquetes insuficientes:**
```json
{
  "success": false,
  "message": "No tienes paquetes disponibles para la disciplina 'Yoga'",
  "data": {
    "reason": "insufficient_packages",
    "discipline_required": {
      "id": 1,
      "name": "Yoga"
    },
    "available_packages": []
  }
}
```

### Resumen de Paquetes por Disciplina

```http
GET /api/me/packages/summary-by-discipline
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Resumen de paquetes obtenido exitosamente",
  "data": {
    "disciplines": [
      {
        "discipline_id": 1,
        "discipline_name": "Yoga",
        "total_packages": 2,
        "total_classes_remaining": 15,
        "packages": [
          {
            "id": 42,
            "package_code": "PKG001-2024",
            "package_name": "Paquete Yoga 10 Clases",
            "remaining_classes": 9,
            "expiry_date": "2025-02-15"
          }
        ]
      }
    ],
    "summary": {
      "total_disciplines": 3,
      "total_packages": 5,
      "total_classes_remaining": 42
    }
  }
}
```

## Lógica de Negocio

### Selección de Paquete para Consumo

Cuando se consume un paquete, el sistema:

1. **Filtra paquetes válidos** para la disciplina específica
2. **Ordena por fecha de expiración** (primero los que expiran antes)
3. **Selecciona el primer paquete** de la lista ordenada
4. **Consume 1 clase** del paquete seleccionado

### Validaciones Implementadas

#### En el Modelo UserPackage:

```php
// Verificar si el paquete es válido
public function getIsValidAttribute(): bool
{
    return $this->status === 'active' &&
           $this->activation_date && $this->activation_date->isPast() &&
           $this->expiry_date && $this->expiry_date->isFuture();
}

// Verificar si puede usarse para una disciplina
public function canUseForDiscipline(int $disciplineId): bool
{
    return $this->is_valid && 
           $this->has_classes && 
           $this->package->discipline_id === $disciplineId;
}

// Consumir clases del paquete
public function useClasses(int $classes = 1): bool
{
    if (!$this->is_valid || $this->remaining_classes < $classes) {
        return false;
    }
    
    $this->increment('used_classes', $classes);
    $this->decrement('remaining_classes', $classes);
    return true;
}
```

#### En el Servicio PackageValidationService:

```php
// Obtener paquetes disponibles para una disciplina
public function getUserAvailablePackagesForDiscipline(int $userId, int $disciplineId): Collection
{
    return UserPackage::query()
        ->where('user_id', $userId)
        ->where('status', 'active')
        ->where('remaining_classes', '>', 0)
        ->whereHas('package', function ($query) use ($disciplineId) {
            $query->where('discipline_id', $disciplineId)
                  ->where('status', 'active');
        })
        ->whereDate('expiry_date', '>=', now())
        ->orderBy('expiry_date', 'asc') // Usar primero los que expiran antes
        ->get();
}
```

## Casos de Uso

### Caso 1: Usuario con Paquetes Válidos
1. Usuario intenta reservar una clase de Yoga
2. Sistema verifica que tiene paquetes de Yoga activos
3. Reserva se procesa exitosamente
4. Se consume 1 clase del paquete más próximo a expirar

### Caso 2: Usuario sin Paquetes para la Disciplina
1. Usuario intenta reservar una clase de Cycling
2. Sistema verifica que NO tiene paquetes de Cycling
3. Reserva es rechazada con mensaje específico
4. Se sugiere comprar paquetes de Cycling

### Caso 3: Usuario con Paquetes Expirados
1. Usuario intenta reservar una clase
2. Sistema verifica que sus paquetes están expirados
3. Reserva es rechazada
4. Se sugiere renovar o comprar nuevos paquetes

## Integración Frontend

### Flujo Recomendado:

1. **Antes de mostrar horarios**: Verificar paquetes disponibles por disciplina
2. **Al seleccionar una clase**: Verificar paquetes específicos para esa clase
3. **Al intentar reservar**: Manejar errores de paquetes insuficientes
4. **Después de reservar**: Mostrar información del paquete consumido

### Manejo de Errores:

```javascript
// Verificar antes de reservar
const checkResponse = await fetch(`/api/class-schedules/${scheduleId}/check-packages`);
const checkData = await checkResponse.json();

if (!checkData.data.can_reserve) {
  // Mostrar mensaje de paquetes insuficientes
  // Redirigir a compra de paquetes
  return;
}

// Proceder con la reserva
const reserveResponse = await fetch(`/api/class-schedules/${scheduleId}/reserve-seats`, {
  method: 'POST',
  body: JSON.stringify(reservationData)
});
```

## Consideraciones Técnicas

- **Transacciones**: Todas las operaciones de reserva usan transacciones de base de datos
- **Bloqueos**: Se implementan bloqueos para evitar condiciones de carrera
- **Logging**: Se registran todas las operaciones de paquetes para auditoría
- **Rollback**: Si falla el consumo de paquete, se revierten las reservas de asientos
