# 📋 Mis Reservas de Asientos

## 🎯 **Endpoint Principal**

**URL:** `GET /api/class-schedules/my-reservations`

**Descripción:** Obtiene todas las reservas de asientos del usuario autenticado, mostrando información completa de las clases, horarios, asientos reservados y estado de las reservas.

---

## 🔐 **Autenticación**

**Requerida:** ✅ Sí (Bearer Token)

```http
Authorization: Bearer {token}
```

---

## 📝 **Parámetros de Consulta (Opcionales)**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `status` | string | Filtrar por estado de reserva | `reserved`, `occupied`, `completed` |
| `date_from` | date | Filtrar desde fecha (Y-m-d) | `2025-01-15` |
| `date_to` | date | Filtrar hasta fecha (Y-m-d) | `2025-01-30` |
| `upcoming` | boolean | Solo reservas futuras | `true`, `false` |

---

## 📊 **Respuesta Exitosa (200)**

```json
{
  "success": true,
  "message": "Reservas obtenidas exitosamente",
  "data": {
    "reservations": [
      {
        "schedule_id": 13,
        "class_name": "Yoga Matutino",
        "instructor_name": "María García",
        "studio_name": "Sala Principal",
        "scheduled_date": "2025-01-15",
        "start_time": "08:00:00",
        "end_time": "09:00:00",
        "class_status": "scheduled",
        "is_upcoming": true,
        "my_seats": [
          {
            "class_schedule_seat_id": 266,
            "seat_id": 1,
            "seat_number": "1.1",
            "row": 1,
            "column": 1,
            "status": "reserved",
            "reserved_at": "2025-01-11T20:30:00.000000Z",
            "expires_at": "2025-01-11T20:45:00.000000Z"
          },
          {
            "class_schedule_seat_id": 267,
            "seat_id": 2,
            "seat_number": "1.2",
            "row": 1,
            "column": 2,
            "status": "reserved",
            "reserved_at": "2025-01-11T20:30:00.000000Z",
            "expires_at": "2025-01-11T20:45:00.000000Z"
          }
        ],
        "total_my_seats": 2,
        "can_cancel": true,
        "cancellation_deadline": "2025-01-15T06:00:00.000000Z",
        "class_datetime": "2025-01-15T08:00:00.000000Z"
      }
    ],
    "summary": {
      "total_reservations": 5,
      "upcoming_reservations": 3,
      "past_reservations": 2,
      "total_seats_reserved": 8
    }
  }
}
```

### 🔍 **Descripción de Campos**

#### **Información de la Clase:**
- `schedule_id`: ID del horario
- `class_name`: Nombre de la clase
- `instructor_name`: Nombre del instructor
- `studio_name`: Nombre del estudio/sala
- `scheduled_date`: Fecha programada
- `start_time`: Hora de inicio
- `end_time`: Hora de finalización
- `class_status`: Estado del horario
- `is_upcoming`: Si es una clase futura

#### **Mis Asientos:**
- `class_schedule_seat_id`: ID único de la reserva (para cancelar)
- `seat_id`: ID del asiento físico
- `seat_number`: Número del asiento (fila.columna)
- `row`: Fila del asiento
- `column`: Columna del asiento
- `status`: Estado actual de la reserva
- `reserved_at`: Cuándo se reservó
- `expires_at`: Cuándo expira la reserva

#### **Control de Cancelación:**
- `can_cancel`: Si puede cancelar la reserva
- `cancellation_deadline`: Límite para cancelar (2 horas antes)

#### **Resumen:**
- `total_reservations`: Total de horarios con reservas
- `upcoming_reservations`: Reservas futuras
- `past_reservations`: Reservas pasadas
- `total_seats_reserved`: Total de asientos reservados

---

## ❌ **Respuestas de Error**

### **404 - Sin Reservas**
```json
{
  "success": false,
  "message": "No tienes reservas de asientos",
  "data": {
    "reservations": [],
    "summary": {
      "total_reservations": 0,
      "upcoming_reservations": 0,
      "past_reservations": 0,
      "total_seats_reserved": 0
    }
  }
}
```

### **401 - No Autenticado**
```json
{
  "message": "Unauthenticated."
}
```

### **422 - Validación**
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "date_from": ["El campo fecha desde debe ser una fecha válida."]
  }
}
```

---

## 🧪 **Ejemplos de Uso**

### **1. Todas las Reservas**
```http
GET /api/class-schedules/my-reservations
Authorization: Bearer {token}
```

### **2. Solo Reservas Activas**
```http
GET /api/class-schedules/my-reservations?status=reserved
Authorization: Bearer {token}
```

### **3. Solo Próximas Clases**
```http
GET /api/class-schedules/my-reservations?upcoming=true
Authorization: Bearer {token}
```

### **4. Reservas de un Mes**
```http
GET /api/class-schedules/my-reservations?date_from=2025-01-01&date_to=2025-01-31
Authorization: Bearer {token}
```

### **5. Reservas Ocupadas en Enero**
```http
GET /api/class-schedules/my-reservations?status=occupied&date_from=2025-01-01&date_to=2025-01-31
Authorization: Bearer {token}
```

---

## 🔄 **Casos de Uso Comunes**

### **Dashboard del Usuario**
```javascript
// Obtener resumen de reservas para el dashboard
const response = await fetch('/api/class-schedules/my-reservations?upcoming=true', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const data = await response.json();
console.log(`Tienes ${data.data.summary.upcoming_reservations} clases próximas`);
```

### **Lista de Próximas Clases**
```javascript
// Mostrar próximas clases en la app
const upcomingClasses = data.data.reservations
  .filter(r => r.is_upcoming)
  .map(r => ({
    id: r.schedule_id,
    name: r.class_name,
    date: r.scheduled_date,
    time: r.start_time,
    instructor: r.instructor_name,
    seats: r.total_my_seats,
    canCancel: r.can_cancel
  }));
```

### **Historial de Clases**
```javascript
// Obtener historial completo
const history = await fetch('/api/class-schedules/my-reservations?upcoming=false', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

---

## 🎯 **Integración con Otros Endpoints**

### **Cancelar Reserva**
```javascript
// Usar class_schedule_seat_id para cancelar
const seatIds = reservation.my_seats.map(seat => seat.class_schedule_seat_id);

await fetch('/api/class-schedules/release-seats', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    class_schedule_seat_ids: seatIds
  })
});
```

### **Ver Mapa de Asientos**
```javascript
// Ver el mapa completo de la clase
const seatMap = await fetch(`/api/class-schedules/${reservation.schedule_id}/seat-map`, {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

---

## 📱 **Consideraciones para Frontend**

### **Estados de Asientos**
- `reserved`: Reservado (puede cancelar)
- `occupied`: Ocupado (usuario presente)
- `completed`: Clase completada

### **Lógica de Cancelación**
```javascript
const canCancel = reservation.can_cancel && 
                 reservation.is_upcoming && 
                 new Date(reservation.cancellation_deadline) > new Date();
```

### **Ordenamiento**
Las reservas vienen ordenadas por:
1. Próximas primero
2. Por fecha/hora ascendente

---

## 🎉 **Resumen**

Este endpoint proporciona una vista completa de todas las reservas del usuario, incluyendo:

- ✅ **Información completa** de clases y horarios
- ✅ **Detalles de asientos** reservados
- ✅ **Control de cancelación** automático
- ✅ **Filtros flexibles** para diferentes vistas
- ✅ **Resumen estadístico** para dashboards
- ✅ **Integración fácil** con otros endpoints

¡Perfecto para crear interfaces de usuario completas y funcionales! 🚀
