# 🪑 API de Distribución de Asientos - Horarios de Clase

## 📋 Descripción General

Esta API permite obtener la distribución completa de asientos de un horario de clase específico, incluyendo:

- **Mapa visual** con distribución en filas y columnas
- **Estados de asientos** (disponible, reservado, ocupado, bloqueado)
- **Información de usuarios** asignados a cada asiento
- **Estadísticas completas** de ocupación
- **Información del estudio** (configuración de sala)

---

## 🔗 Endpoint

```http
GET /api/class-schedules/seat-map/{classSchedule}
```

### 🔐 Autenticación Requerida
```http
Authorization: Bearer {tu_token_aqui}
```

---

## 📝 Parámetros

| Parámetro | Tipo | Ubicación | Descripción |
|-----------|------|-----------|-------------|
| `classSchedule` | integer | URL | **Requerido.** ID del horario de clase |

---

## 📤 Ejemplo de Solicitud

### cURL
```bash
curl -X GET "http://backend-resistanc.test/api/class-schedules/seat-map/5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer tu_token_aqui"
```

### JavaScript (Fetch)
```javascript
const response = await fetch('http://backend-resistanc.test/api/class-schedules/seat-map/5', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer tu_token_aqui'
  }
});

const seatMap = await response.json();
console.log(seatMap);
```

### PHP (Guzzle)
```php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->get('http://backend-resistanc.test/api/class-schedules/seat-map/5', [
    'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer tu_token_aqui'
    ]
]);

$seatMap = json_decode($response->getBody(), true);
```

---

## 📥 Respuesta Exitosa (200)

### Estructura de la Respuesta

```json
{
  "studio_info": {
    "id": 3,
    "name": "Cycling Studio C",
    "rows": 4,
    "columns": 4,
    "total_positions": 16,
    "addressing": "left_to_right",
    "capacity": 15
  },
  "seat_grid": {
    "1": {
      "1": {
        "exists": true,
        "seat_id": 45,
        "assignment_id": 123,
        "seat_number": "1.1",
        "row": 1,
        "column": 1,
        "status": "available",
        "is_active": true,
        "user": null,
        "reserved_at": null,
        "expires_at": null,
        "is_expired": false
      },
      "2": {
        "exists": true,
        "seat_id": 46,
        "assignment_id": 124,
        "seat_number": "1.2",
        "row": 1,
        "column": 2,
        "status": "reserved",
        "is_active": true,
        "user": {
          "id": 15,
          "name": "María García",
          "email": "maria@example.com"
        },
        "reserved_at": "2024-06-15T10:30:00.000Z",
        "expires_at": "2024-06-15T10:45:00.000Z",
        "is_expired": false
      },
      "3": {
        "exists": false,
        "seat_id": null,
        "assignment_id": null,
        "seat_number": null,
        "row": 1,
        "column": 3,
        "status": "empty",
        "is_active": false,
        "user": null,
        "reserved_at": null,
        "expires_at": null,
        "is_expired": false
      }
    }
  },
  "seats_by_status": {
    "available": [
      {
        "id": 45,
        "assignment_id": 123,
        "seat_number": "1.1",
        "row": 1,
        "column": 1,
        "status": "available",
        "user": null,
        "reserved_at": null,
        "expires_at": null,
        "is_expired": false
      }
    ],
    "reserved": [
      {
        "id": 46,
        "assignment_id": 124,
        "seat_number": "1.2",
        "row": 1,
        "column": 2,
        "status": "reserved",
        "user": {
          "id": 15,
          "name": "María García",
          "email": "maria@example.com"
        },
        "reserved_at": "2024-06-15T10:30:00.000Z",
        "expires_at": "2024-06-15T10:45:00.000Z",
        "is_expired": false
      }
    ],
    "occupied": [],
    "blocked": []
  },
  "summary": {
    "total_seats": 15,
    "available_count": 10,
    "reserved_count": 3,
    "occupied_count": 2,
    "completed_count": 0,
    "blocked_count": 0,
    "expired_count": 0,
    "empty_positions": 1
  }
}
```

---

## 📊 Descripción de Campos

### `studio_info`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID del estudio/sala |
| `name` | string | Nombre del estudio |
| `rows` | integer | Número de filas configuradas |
| `columns` | integer | Número de columnas configuradas |
| `total_positions` | integer | Total de posiciones posibles (filas × columnas) |
| `addressing` | string | Direccionamiento de asientos (`left_to_right`, `right_to_left`, etc.) |
| `capacity` | integer | Capacidad máxima del estudio |

### `seat_grid`
Matriz bidimensional organizada por `[fila][columna]` con la información de cada posición:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `exists` | boolean | Si existe un asiento en esta posición |
| `seat_id` | integer\|null | ID del asiento (null si no existe) |
| `assignment_id` | integer\|null | ID de la asignación del asiento al horario |
| `seat_number` | string\|null | Número identificador del asiento |
| `row` | integer | Número de fila |
| `column` | integer | Número de columna |
| `status` | string | Estado: `available`, `reserved`, `occupied`, `blocked`, `empty` |
| `is_active` | boolean | Si el asiento está activo |
| `user` | object\|null | Información del usuario asignado |
| `reserved_at` | string\|null | Fecha/hora de reserva (ISO 8601) |
| `expires_at` | string\|null | Fecha/hora de expiración (ISO 8601) |
| `is_expired` | boolean | Si la reserva ha expirado |

### `seats_by_status`
Asientos agrupados por estado para fácil acceso:

- `available`: Asientos disponibles para reservar
- `reserved`: Asientos reservados temporalmente
- `occupied`: Asientos ocupados/confirmados
- `blocked`: Asientos bloqueados por administración
- `Completed`: Asientos con clase completada

### `summary`
Estadísticas resumidas:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `total_seats` | integer | Total de asientos configurados |
| `available_count` | integer | Asientos disponibles |
| `reserved_count` | integer | Asientos reservados |
| `occupied_count` | integer | Asientos ocupados |
| `completed_count` | integer | Asientos completados |
| `blocked_count` | integer | Asientos bloqueados |
| `expired_count` | integer | Reservas expiradas |
| `empty_positions` | integer | Posiciones sin asiento |

---

## ❌ Respuestas de Error

### 404 - Horario no encontrado
```json
{
  "message": "No query results for model [App\\Models\\ClassSchedule] 5"
}
```

### 401 - No autenticado
```json
{
  "message": "Unauthenticated."
}
```

---

## 🎯 Casos de Uso

### 1. **Mostrar Mapa Visual**
Usar `seat_grid` para renderizar la distribución visual de asientos en una interfaz web o móvil.

### 2. **Filtrar por Estado**
Usar `seats_by_status` para mostrar solo asientos disponibles, reservados, etc.

### 3. **Estadísticas Rápidas**
Usar `summary` para mostrar contadores y métricas de ocupación.

### 4. **Información del Estudio**
Usar `studio_info` para mostrar detalles de la sala y configuración.

---

## 🔄 Estados de Asientos

| Estado | Descripción | Color Sugerido |
|--------|-------------|----------------|
| `available` | Disponible para reservar | 🟢 Verde |
| `reserved` | Reservado temporalmente | 🟡 Amarillo |
| `occupied` | Ocupado/Confirmado | 🔴 Rojo |
| `blocked` | Bloqueado por administración | ⚫ Gris |
| `Completed` | Clase completada | 🔵 Azul |
| `empty` | Posición sin asiento | ⚪ Blanco |

---

## 🧪 Pruebas Rápidas

### Postman
1. Crear nueva request GET
2. URL: `http://backend-resistanc.test/api/class-schedules/seat-map/5`
3. Headers: `Authorization: Bearer tu_token`
4. Enviar

### Navegador (con token)
```
http://backend-resistanc.test/api/class-schedules/seat-map/5?token=tu_token_aqui
```

---

## 📱 Ejemplo de Implementación Frontend

### React/Vue.js
```javascript
// Renderizar mapa de asientos
const renderSeatGrid = (seatGrid) => {
  return Object.keys(seatGrid).map(row => (
    <div key={row} className="seat-row">
      {Object.keys(seatGrid[row]).map(col => {
        const seat = seatGrid[row][col];
        return (
          <div 
            key={`${row}-${col}`}
            className={`seat seat-${seat.status}`}
            title={seat.exists ? `${seat.seat_number} - ${seat.status}` : 'Posición vacía'}
          >
            {seat.exists ? seat.seat_number : '+'}
          </div>
        );
      })}
    </div>
  ));
};
```

---

## 🔧 Notas Técnicas

- **Formato de fechas**: ISO 8601 (UTC)
- **Autenticación**: Bearer Token requerido
- **Rate limiting**: Aplicable según configuración del servidor
- **Cache**: Respuesta puede ser cacheada por 30 segundos
- **Tiempo real**: Para actualizaciones en tiempo real, considerar WebSockets o polling
