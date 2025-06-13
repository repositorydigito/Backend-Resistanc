# 🎯 API de Reserva de Asientos - Documentación Completa

## 📋 Descripción General

Esta API permite al usuario autenticado reservar múltiples asientos en un horario de clase específico. Los asientos se reservan temporalmente y expiran después del tiempo especificado.

---

## 🔗 Endpoint

```http
POST /api/class-schedules/{classSchedule}/reserve-seats
```

### 🔐 Autenticación Requerida
```http
Authorization: Bearer {tu_token_aqui}
```

---

## 📝 Parámetros

### **URL Parameters**
| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `classSchedule` | integer | ID del horario de clase | `5` |

### **Body Parameters (JSON)**
| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `seat_ids` | array | ✅ Sí | Array de IDs de asientos a reservar (máx 10) | `[1, 2, 3]` |
| `minutes_to_expire` | integer | ❌ No | Minutos antes de que expire la reserva (5-60, default: 15) | `15` |

---

## 📤 Ejemplos de Solicitudes

### **1. Reservar un Solo Asiento**
```bash
curl -X POST "http://backend-resistanc.test/api/class-schedules/5/reserve-seats" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer tu_token_aqui" \
  -d '{
    "seat_ids": [1]
  }'
```

### **2. Reservar Múltiples Asientos**
```bash
curl -X POST "http://backend-resistanc.test/api/class-schedules/5/reserve-seats" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer tu_token_aqui" \
  -d '{
    "seat_ids": [1, 2, 3],
    "minutes_to_expire": 20
  }'
```

### **3. Reservar con Tiempo Personalizado**
```bash
curl -X POST "http://backend-resistanc.test/api/class-schedules/5/reserve-seats" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer tu_token_aqui" \
  -d '{
    "seat_ids": [4, 5],
    "minutes_to_expire": 30
  }'
```

---

## 📥 Respuestas

### ✅ **Respuesta Exitosa (200)**
```json
{
  "success": true,
  "message": "Asientos reservados exitosamente",
  "data": {
    "reserved_seats": [
      {
        "seat_id": 1,
        "assignment_id": 123,
        "seat_number": "1.1",
        "row": 1,
        "column": 1,
        "status": "reserved",
        "reserved_at": "2024-06-15T10:30:00.000Z",
        "expires_at": "2024-06-15T10:45:00.000Z"
      },
      {
        "seat_id": 2,
        "assignment_id": 124,
        "seat_number": "1.2",
        "row": 1,
        "column": 2,
        "status": "reserved",
        "reserved_at": "2024-06-15T10:30:00.000Z",
        "expires_at": "2024-06-15T10:45:00.000Z"
      }
    ],
    "reservation_summary": {
      "total_reserved": 2,
      "expires_in_minutes": 15,
      "expires_at": "2024-06-15T10:45:00.000Z",
      "user_id": 10,
      "schedule_id": 5,
      "class_name": "Hatha Yoga",
      "studio_name": "Sala Yoga A",
      "scheduled_date": "2024-06-15",
      "start_time": "08:00:00"
    }
  }
}
```

### ❌ **Asientos No Disponibles (400)**
```json
{
  "success": false,
  "message": "Algunos asientos no están disponibles",
  "data": {
    "unavailable_seats": [
      {
        "seat_id": 1,
        "current_status": "reserved",
        "user_id": 8
      }
    ],
    "available_seats": [2, 3]
  }
}
```

### ❌ **Asientos No Asignados al Horario (400)**
```json
{
  "success": false,
  "message": "Algunos asientos no están asignados a este horario",
  "data": {
    "missing_seat_ids": [99, 100],
    "available_seat_ids": [1, 2, 3, 4, 5]
  }
}
```

### ❌ **Horario No Encontrado (404)**
```json
{
  "success": false,
  "message": "Horario de clase no encontrado",
  "data": null
}
```

### ❌ **Reservas Cerradas (422)**
```json
{
  "success": false,
  "message": "Las reservas se cierran 2 horas antes del inicio de la clase",
  "data": {
    "reason": "booking_closed"
  }
}
```

### ❌ **Horario Cancelado (422)**
```json
{
  "success": false,
  "message": "No se puede reservar en un horario cancelado",
  "data": {
    "reason": "schedule_cancelled"
  }
}
```

### ❌ **Horario Pasado (422)**
```json
{
  "success": false,
  "message": "No se puede reservar en un horario pasado",
  "data": {
    "reason": "schedule_past"
  }
}
```

### ❌ **Validación de Datos (422)**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "seat_ids": ["Debe especificar al menos un asiento para reservar"],
    "minutes_to_expire": ["Los minutos de expiración deben ser un número entero"]
  }
}
```

### ❌ **No Autenticado (401)**
```json
{
  "message": "Unauthenticated."
}
```

### ❌ **Error Interno (500)**
```json
{
  "success": false,
  "message": "Error interno al reservar asientos",
  "data": null
}
```

---

## 💻 Ejemplos de Código

### **JavaScript (Fetch)**
```javascript
async function reserveSeats(scheduleId, seatIds, minutesToExpire = 15, token) {
  try {
    const response = await fetch(`http://backend-resistanc.test/api/class-schedules/${scheduleId}/reserve-seats`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        seat_ids: seatIds,
        minutes_to_expire: minutesToExpire
      })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || `HTTP error! status: ${response.status}`);
    }

    console.log('✅ Asientos reservados:', data.data.reserved_seats.length);
    console.log('⏰ Expiran en:', data.data.reservation_summary.expires_in_minutes, 'minutos');
    
    return data;
  } catch (error) {
    console.error('❌ Error al reservar asientos:', error.message);
    throw error;
  }
}

// Ejemplos de uso
const token = 'tu_token_aqui';

// Reservar un asiento
reserveSeats(5, [1], 15, token);

// Reservar múltiples asientos
reserveSeats(5, [1, 2, 3], 20, token);

// Reservar con tiempo personalizado
reserveSeats(5, [4, 5], 30, token);
```

### **JavaScript (Axios)**
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://backend-resistanc.test/api',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${localStorage.getItem('token')}`
  }
});

async function reserveSeats(scheduleId, seatIds, minutesToExpire = 15) {
  try {
    const response = await api.post(`/class-schedules/${scheduleId}/reserve-seats`, {
      seat_ids: seatIds,
      minutes_to_expire: minutesToExpire
    });

    return response.data;
  } catch (error) {
    if (error.response?.status === 400) {
      console.error('Asientos no disponibles:', error.response.data.data.unavailable_seats);
    } else if (error.response?.status === 422) {
      console.error('No se puede reservar:', error.response.data.data.reason);
    }
    throw error;
  }
}

// Uso con manejo de errores
reserveSeats(5, [1, 2, 3])
  .then(data => {
    console.log('Reserva exitosa:', data.data.reservation_summary);
  })
  .catch(error => {
    console.error('Error en reserva:', error.response?.data?.message);
  });
```

### **PHP (Guzzle)**
```php
<?php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SeatReservationService {
    private $client;
    private $token;

    public function __construct(string $token) {
        $this->token = $token;
        $this->client = new Client([
            'base_uri' => 'http://backend-resistanc.test/api/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json'
            ]
        ]);
    }

    public function reserveSeats(int $scheduleId, array $seatIds, int $minutesToExpire = 15): array {
        try {
            $response = $this->client->post("class-schedules/{$scheduleId}/reserve-seats", [
                'json' => [
                    'seat_ids' => $seatIds,
                    'minutes_to_expire' => $minutesToExpire
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $errorData = json_decode($response->getBody(), true);
                
                if ($response->getStatusCode() === 400) {
                    throw new Exception('Asientos no disponibles: ' . $errorData['message']);
                } elseif ($response->getStatusCode() === 422) {
                    throw new Exception('No se puede reservar: ' . $errorData['data']['reason']);
                }
            }
            
            throw new Exception('Error al reservar asientos: ' . $e->getMessage());
        }
    }
}

// Uso
$service = new SeatReservationService('tu_token_aqui');

try {
    $result = $service->reserveSeats(5, [1, 2, 3], 20);
    echo "Asientos reservados: " . $result['data']['reservation_summary']['total_reserved'] . "\n";
    echo "Expiran en: " . $result['data']['reservation_summary']['expires_in_minutes'] . " minutos\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### **Python (Requests)**
```python
import requests
from typing import List, Dict, Any

class SeatReservationAPI:
    def __init__(self, base_url: str, token: str):
        self.base_url = base_url
        self.headers = {
            'Content-Type': 'application/json',
            'Authorization': f'Bearer {token}',
            'Accept': 'application/json'
        }

    def reserve_seats(self, schedule_id: int, seat_ids: List[int], minutes_to_expire: int = 15) -> Dict[str, Any]:
        """Reservar asientos en un horario específico"""
        url = f'{self.base_url}/api/class-schedules/{schedule_id}/reserve-seats'
        
        payload = {
            'seat_ids': seat_ids,
            'minutes_to_expire': minutes_to_expire
        }

        try:
            response = requests.post(url, json=payload, headers=self.headers)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.HTTPError as e:
            if response.status_code == 400:
                error_data = response.json()
                raise ValueError(f"Asientos no disponibles: {error_data['message']}")
            elif response.status_code == 422:
                error_data = response.json()
                raise ValueError(f"No se puede reservar: {error_data['data']['reason']}")
            raise e

# Uso
api = SeatReservationAPI('http://backend-resistanc.test', 'tu_token_aqui')

try:
    result = api.reserve_seats(5, [1, 2, 3], 20)
    summary = result['data']['reservation_summary']
    print(f"✅ {summary['total_reserved']} asientos reservados")
    print(f"⏰ Expiran en {summary['expires_in_minutes']} minutos")
    print(f"📅 Clase: {summary['class_name']} en {summary['studio_name']}")
except ValueError as e:
    print(f"❌ Error: {e}")
```

---

## 🔍 Validaciones y Restricciones

### **Validaciones de Entrada:**
- ✅ `seat_ids` debe ser un array con al menos 1 elemento
- ✅ Máximo 10 asientos por reserva
- ✅ Cada `seat_id` debe existir en la base de datos
- ✅ `minutes_to_expire` debe estar entre 5 y 60 minutos

### **Restricciones de Negocio:**
- ✅ Solo usuarios autenticados pueden reservar
- ✅ No se puede reservar en horarios cancelados
- ✅ No se puede reservar en horarios pasados
- ✅ Las reservas se cierran 2 horas antes del inicio
- ✅ Solo se pueden reservar asientos disponibles
- ✅ Los asientos deben estar asignados al horario específico

### **Características de Seguridad:**
- ✅ Transacciones de base de datos para consistencia
- ✅ Bloqueo de registros para evitar condiciones de carrera
- ✅ Validación de permisos por usuario autenticado
- ✅ Logging de errores para debugging

---

## 🎯 Casos de Uso

### **1. Reserva Individual**
```javascript
// Reservar un asiento específico
reserveSeats(5, [1], 15, token);
```

### **2. Reserva Grupal**
```javascript
// Reservar asientos para un grupo
reserveSeats(5, [1, 2, 3, 4], 30, token);
```

### **3. Reserva con Tiempo Extendido**
```javascript
// Reservar con más tiempo para decidir
reserveSeats(5, [5, 6], 45, token);
```

### **4. Manejo de Errores**
```javascript
try {
  await reserveSeats(5, [1, 2], 15, token);
} catch (error) {
  if (error.message.includes('no están disponibles')) {
    // Mostrar asientos alternativos
  } else if (error.message.includes('reservas se cierran')) {
    // Informar que ya no se puede reservar
  }
}
```

---

## 📋 Checklist de Pruebas

- [ ] ✅ Reserva exitosa de un asiento
- [ ] ✅ Reserva exitosa de múltiples asientos
- [ ] ❌ Error con asientos no disponibles
- [ ] ❌ Error con asientos inexistentes
- [ ] ❌ Error con horario no encontrado
- [ ] ❌ Error con horario cancelado
- [ ] ❌ Error con horario pasado
- [ ] ❌ Error con reservas cerradas
- [ ] 🔐 Error sin autenticación
- [ ] 📊 Validación de datos de entrada
- [ ] ⏰ Verificación de tiempo de expiración

---

## 🎉 **¡Endpoint Listo para Usar!**

Ahora puedes reservar múltiples asientos para el usuario autenticado usando:

```
POST /api/class-schedules/{classSchedule}/reserve-seats
```

Con el cuerpo JSON:
```json
{
  "seat_ids": [1, 2, 3],
  "minutes_to_expire": 15
}
```
