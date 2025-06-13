# 🎯 Operaciones con class_schedule_seat

## 📋 **Concepto Clave**

La tabla `class_schedule_seat` es el **corazón del sistema de reservas**. Cada registro representa:

- ✅ **Un asiento específico** asignado a **un horario específico**
- ✅ **El estado actual** del asiento (available, reserved, occupied, etc.)
- ✅ **El usuario** que lo tiene reservado (si aplica)
- ✅ **Timestamps** de reserva y expiración

### 🔑 **ID Importante: `class_schedule_seat.id`**

Este es el **ID que necesitas guardar** para futuras operaciones:
- 🎯 Cambiar estado del asiento
- 🎯 Liberar reserva
- 🎯 Marcar como ocupado
- 🎯 Transferir a otro usuario

---

## 🚀 **Flujo Completo de Reserva**

### **1. Reservar Asientos**

**Endpoint:** `POST /api/class-schedules/{id}/reserve-seats`

**Request:**
```json
{
  "seat_ids": [1, 2, 3],
  "minutes_to_expire": 15
}
```

**Response:**
```json
{
  "success": true,
  "message": "Asientos reservados exitosamente",
  "data": {
    "reserved_seats": [
      {
        "class_schedule_seat_id": 267,  // 🎯 ¡ESTE ES EL ID QUE NECESITAS!
        "seat_id": 1,
        "seat_number": "1.1",
        "row": 1,
        "column": 1,
        "status": "reserved",
        "user_id": 10,
        "reserved_at": "2025-01-11T20:30:00.000000Z",
        "expires_at": "2025-01-11T20:45:00.000000Z",
        "assignment_id": 267,  // Alias del class_schedule_seat_id
        "schedule_id": 13
      },
      {
        "class_schedule_seat_id": 268,  // 🎯 Otro ID importante
        "seat_id": 2,
        "seat_number": "1.2",
        // ... más datos
      }
    ],
    "reservation_summary": {
      "total_reserved": 3,
      "expires_in_minutes": 15,
      "user_id": 10,
      "schedule_id": 13
    }
  }
}
```

### **2. Liberar Reservas**

**Endpoint:** `POST /api/class-schedules/release-seats`

**Request:**
```json
{
  "class_schedule_seat_ids": [267, 268, 269]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Reservas liberadas exitosamente",
  "data": {
    "released_seats": [
      {
        "class_schedule_seat_id": 267,
        "seat_id": 1,
        "seat_number": "1.1",
        "previous_status": "reserved",
        "new_status": "available",
        "released_at": "2025-01-11T20:35:00.000000Z"
      }
    ],
    "release_summary": {
      "total_released": 3,
      "user_id": 10
    }
  }
}
```

---

## 🎯 **Casos de Uso Prácticos**

### **Caso 1: Usuario Reserva y Luego Cancela**

```javascript
// 1. Usuario reserva asientos
const reserveResponse = await fetch('/api/class-schedules/13/reserve-seats', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    seat_ids: [1, 2, 3],
    minutes_to_expire: 15
  })
});

const reserveData = await reserveResponse.json();

// 2. Guardar los IDs para futuras operaciones
const classScheduleSeatIds = reserveData.data.reserved_seats.map(
  seat => seat.class_schedule_seat_id
);
// classScheduleSeatIds = [267, 268, 269]

// 3. Usuario decide cancelar
const releaseResponse = await fetch('/api/class-schedules/release-seats', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    class_schedule_seat_ids: classScheduleSeatIds
  })
});
```

### **Caso 2: Marcar Asientos como Ocupados**

```javascript
// Después de que el usuario llega a la clase
const occupyResponse = await fetch('/api/class-schedules/occupy-seats', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    class_schedule_seat_ids: [267, 268, 269]
  })
});
```

---

## 🗃️ **Estructura de la Tabla class_schedule_seat**

```sql
CREATE TABLE class_schedule_seat (
    id BIGINT PRIMARY KEY,                    -- 🎯 Este es el ID que necesitas
    class_schedules_id BIGINT,                -- Horario
    seats_id BIGINT,                          -- Asiento físico
    user_id BIGINT NULL,                      -- Usuario que lo reservó
    status ENUM('available', 'reserved', 'occupied', 'Completed', 'blocked'),
    reserved_at TIMESTAMP NULL,               -- Cuándo se reservó
    expires_at TIMESTAMP NULL,                -- Cuándo expira la reserva
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### **Estados Posibles:**

- 🟢 **`available`**: Disponible para reservar
- 🟡 **`reserved`**: Reservado temporalmente
- 🔴 **`occupied`**: Ocupado (usuario presente)
- ✅ **`Completed`**: Clase completada
- ⚫ **`blocked`**: Bloqueado (mantenimiento, etc.)

---

## 🔄 **Operaciones Futuras Sugeridas**

### **1. Marcar como Ocupado**
```http
POST /api/class-schedules/occupy-seats
{
  "class_schedule_seat_ids": [267, 268, 269]
}
```

### **2. Transferir Reserva**
```http
POST /api/class-schedules/transfer-seats
{
  "class_schedule_seat_ids": [267, 268, 269],
  "new_user_id": 15
}
```

### **3. Extender Reserva**
```http
POST /api/class-schedules/extend-reservation
{
  "class_schedule_seat_ids": [267, 268, 269],
  "additional_minutes": 10
}
```

### **4. Bloquear Asientos**
```http
POST /api/class-schedules/block-seats
{
  "class_schedule_seat_ids": [267, 268, 269],
  "reason": "Mantenimiento"
}
```

---

## 💡 **Tips de Implementación**

### **Frontend - Guardar IDs**
```javascript
// Al reservar, guardar los IDs en el estado local
const [reservedSeatIds, setReservedSeatIds] = useState([]);

const handleReserve = async (seatIds) => {
  const response = await reserveSeats(seatIds);
  if (response.success) {
    const ids = response.data.reserved_seats.map(s => s.class_schedule_seat_id);
    setReservedSeatIds(ids);
    
    // También guardar en localStorage para persistencia
    localStorage.setItem('reservedSeats', JSON.stringify(ids));
  }
};
```

### **Backend - Validaciones**
```php
// Siempre verificar que el usuario puede operar sobre estos asientos
$assignments = ClassScheduleSeat::whereIn('id', $assignmentIds)
    ->where('user_id', Auth::id()) // Solo sus propias reservas
    ->get();
```

### **Base de Datos - Índices**
```sql
-- Índices para optimizar consultas
CREATE INDEX idx_class_schedule_seat_user_status ON class_schedule_seat(user_id, status);
CREATE INDEX idx_class_schedule_seat_schedule_status ON class_schedule_seat(class_schedules_id, status);
CREATE INDEX idx_class_schedule_seat_expires ON class_schedule_seat(expires_at);
```

---

## 🎯 **Resumen**

### **Lo Más Importante:**

1. ✅ **`class_schedule_seat.id`** es el ID que necesitas para todas las operaciones
2. ✅ Se devuelve como **`class_schedule_seat_id`** en las respuestas
3. ✅ Úsalo para liberar, transferir, ocupar, etc.
4. ✅ Siempre valida que el usuario tenga permisos sobre ese ID

### **Endpoints Disponibles:**

- 🎯 **Reservar**: `POST /api/class-schedules/{id}/reserve-seats`
- 🎯 **Liberar**: `POST /api/class-schedules/release-seats`
- 🎯 **Ver Mapa**: `GET /api/class-schedules/{id}/seat-map`

¡Con estos IDs puedes implementar cualquier operación de gestión de asientos! 🚀
