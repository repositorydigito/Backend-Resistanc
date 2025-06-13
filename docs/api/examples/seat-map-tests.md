# 🧪 Ejemplos de Pruebas - API de Distribución de Asientos

## 🚀 Pruebas Rápidas

### 1. **Prueba con cURL**

```bash
# Reemplaza {ID_HORARIO} con el ID real del horario
# Reemplaza {TU_TOKEN} con tu token de autenticación

curl -X GET "http://backend-resistanc.test/api/class-schedules/seat-map/5" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {TU_TOKEN}" \
  -H "Content-Type: application/json"
```

### 2. **Prueba con HTTPie**

```bash
# Instalar HTTPie: pip install httpie
http GET http://backend-resistanc.test/api/class-schedules/seat-map/5 \
  Authorization:"Bearer {TU_TOKEN}" \
  Accept:application/json
```

### 3. **Prueba con Postman**

#### Configuración:
- **Método**: GET
- **URL**: `http://backend-resistanc.test/api/class-schedules/seat-map/5`
- **Headers**:
  - `Authorization`: `Bearer {TU_TOKEN}`
  - `Accept`: `application/json`

#### Colección Postman (JSON):
```json
{
  "info": {
    "name": "Seat Map API Tests",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Get Seat Map",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{token}}",
            "type": "text"
          },
          {
            "key": "Accept",
            "value": "application/json",
            "type": "text"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/class-schedules/seat-map/{{schedule_id}}",
          "host": ["{{base_url}}"],
          "path": ["api", "class-schedules", "seat-map", "{{schedule_id}}"]
        }
      }
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://backend-resistanc.test"
    },
    {
      "key": "schedule_id",
      "value": "5"
    },
    {
      "key": "token",
      "value": "tu_token_aqui"
    }
  ]
}
```

---

## 💻 Ejemplos de Código

### JavaScript (Fetch API)

```javascript
async function getSeatMap(scheduleId, token) {
  try {
    const response = await fetch(`http://backend-resistanc.test/api/class-schedules/seat-map/${scheduleId}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const seatMap = await response.json();
    
    // Procesar respuesta
    console.log('Studio Info:', seatMap.studio_info);
    console.log('Summary:', seatMap.summary);
    
    // Renderizar mapa
    renderSeatGrid(seatMap.seat_grid);
    
    return seatMap;
  } catch (error) {
    console.error('Error fetching seat map:', error);
    throw error;
  }
}

// Función para renderizar el mapa de asientos
function renderSeatGrid(seatGrid) {
  const container = document.getElementById('seat-map');
  container.innerHTML = '';
  
  Object.keys(seatGrid).forEach(row => {
    const rowDiv = document.createElement('div');
    rowDiv.className = 'seat-row';
    
    Object.keys(seatGrid[row]).forEach(col => {
      const seat = seatGrid[row][col];
      const seatDiv = document.createElement('div');
      
      seatDiv.className = `seat seat-${seat.status}`;
      seatDiv.textContent = seat.exists ? seat.seat_number : '+';
      seatDiv.title = seat.exists ? 
        `${seat.seat_number} - ${seat.status}${seat.user ? ` (${seat.user.name})` : ''}` : 
        'Posición vacía';
      
      rowDiv.appendChild(seatDiv);
    });
    
    container.appendChild(rowDiv);
  });
}

// Uso
getSeatMap(5, 'tu_token_aqui');
```

### JavaScript (Axios)

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://backend-resistanc.test/api',
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${localStorage.getItem('token')}`
  }
});

async function getSeatMap(scheduleId) {
  try {
    const response = await api.get(`/class-schedules/seat-map/${scheduleId}`);
    return response.data;
  } catch (error) {
    if (error.response?.status === 404) {
      throw new Error('Horario no encontrado');
    } else if (error.response?.status === 401) {
      throw new Error('Token de autenticación inválido');
    }
    throw error;
  }
}
```

### PHP (Guzzle)

```php
<?php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SeatMapService
{
    private $client;
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->client = new Client([
            'base_uri' => 'http://backend-resistanc.test/api/',
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$token}"
            ]
        ]);
    }

    public function getSeatMap(int $scheduleId): array
    {
        try {
            $response = $this->client->get("class-schedules/seat-map/{$scheduleId}");
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                throw new Exception('Horario no encontrado');
            }
            throw new Exception('Error al obtener mapa de asientos: ' . $e->getMessage());
        }
    }

    public function renderSeatGrid(array $seatGrid): string
    {
        $html = '<div class="seat-map">';
        
        foreach ($seatGrid as $row => $columns) {
            $html .= '<div class="seat-row">';
            foreach ($columns as $col => $seat) {
                $status = $seat['status'];
                $content = $seat['exists'] ? $seat['seat_number'] : '+';
                $title = $seat['exists'] ? 
                    "{$seat['seat_number']} - {$status}" . 
                    ($seat['user'] ? " ({$seat['user']['name']})" : '') : 
                    'Posición vacía';
                
                $html .= "<div class='seat seat-{$status}' title='{$title}'>{$content}</div>";
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
}

// Uso
$seatMapService = new SeatMapService('tu_token_aqui');
$seatMap = $seatMapService->getSeatMap(5);

echo "Estudio: " . $seatMap['studio_info']['name'] . "\n";
echo "Total asientos: " . $seatMap['summary']['total_seats'] . "\n";
echo "Disponibles: " . $seatMap['summary']['available_count'] . "\n";
```

### Python (Requests)

```python
import requests
import json
from typing import Dict, Any

class SeatMapAPI:
    def __init__(self, base_url: str, token: str):
        self.base_url = base_url
        self.session = requests.Session()
        self.session.headers.update({
            'Accept': 'application/json',
            'Authorization': f'Bearer {token}'
        })

    def get_seat_map(self, schedule_id: int) -> Dict[str, Any]:
        """Obtiene el mapa de asientos para un horario específico"""
        try:
            response = self.session.get(
                f'{self.base_url}/api/class-schedules/seat-map/{schedule_id}'
            )
            response.raise_for_status()
            return response.json()
        except requests.exceptions.HTTPError as e:
            if response.status_code == 404:
                raise ValueError('Horario no encontrado')
            elif response.status_code == 401:
                raise ValueError('Token de autenticación inválido')
            raise e

    def print_seat_summary(self, seat_map: Dict[str, Any]) -> None:
        """Imprime un resumen del mapa de asientos"""
        studio = seat_map['studio_info']
        summary = seat_map['summary']
        
        print(f"🏢 Estudio: {studio['name']}")
        print(f"📐 Dimensiones: {studio['rows']}x{studio['columns']}")
        print(f"📊 Resumen:")
        print(f"   • Total asientos: {summary['total_seats']}")
        print(f"   • Disponibles: {summary['available_count']}")
        print(f"   • Reservados: {summary['reserved_count']}")
        print(f"   • Ocupados: {summary['occupied_count']}")
        print(f"   • Bloqueados: {summary['blocked_count']}")

# Uso
api = SeatMapAPI('http://backend-resistanc.test', 'tu_token_aqui')
seat_map = api.get_seat_map(5)
api.print_seat_summary(seat_map)
```

---

## 🎨 CSS para Visualización

```css
.seat-map {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.seat-row {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.seat {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  font-weight: bold;
  font-size: 12px;
  cursor: pointer;
  transition: transform 0.2s;
  border: 2px solid transparent;
}

.seat:hover {
  transform: scale(1.1);
}

.seat-available {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border-color: #047857;
}

.seat-reserved {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border-color: #b45309;
}

.seat-occupied {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  border-color: #b91c1c;
}

.seat-blocked {
  background: linear-gradient(135deg, #6b7280, #4b5563);
  color: white;
  border-color: #374151;
}

.seat-empty {
  background: #f9fafb;
  color: #9ca3af;
  border: 2px dashed #d1d5db;
  font-size: 16px;
}
```

---

## 🔍 Validación de Respuesta

### Schema JSON para validación:

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["studio_info", "seat_grid", "seats_by_status", "summary"],
  "properties": {
    "studio_info": {
      "type": "object",
      "required": ["id", "name", "rows", "columns", "total_positions"],
      "properties": {
        "id": {"type": "integer"},
        "name": {"type": "string"},
        "rows": {"type": "integer"},
        "columns": {"type": "integer"},
        "total_positions": {"type": "integer"},
        "addressing": {"type": "string"},
        "capacity": {"type": "integer"}
      }
    },
    "summary": {
      "type": "object",
      "required": ["total_seats", "available_count", "reserved_count"],
      "properties": {
        "total_seats": {"type": "integer"},
        "available_count": {"type": "integer"},
        "reserved_count": {"type": "integer"},
        "occupied_count": {"type": "integer"},
        "blocked_count": {"type": "integer"},
        "expired_count": {"type": "integer"},
        "empty_positions": {"type": "integer"}
      }
    }
  }
}
```

---

## 🚨 Manejo de Errores

```javascript
async function handleSeatMapRequest(scheduleId, token) {
  try {
    const seatMap = await getSeatMap(scheduleId, token);
    
    // Verificar si hay error en la respuesta
    if (seatMap.error) {
      throw new Error(seatMap.error);
    }
    
    return seatMap;
  } catch (error) {
    // Manejo específico de errores
    if (error.message.includes('404')) {
      alert('El horario solicitado no existe');
    } else if (error.message.includes('401')) {
      alert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente');
      // Redirigir a login
    } else if (error.message.includes('Network')) {
      alert('Error de conexión. Verifica tu internet');
    } else {
      alert('Error inesperado: ' + error.message);
    }
    
    console.error('Error completo:', error);
  }
}
```

---

## 📋 Checklist de Pruebas

- [ ] ✅ Respuesta exitosa con horario válido
- [ ] ❌ Error 404 con horario inexistente  
- [ ] 🔐 Error 401 sin token de autenticación
- [ ] 🔐 Error 401 con token inválido
- [ ] 📊 Validar estructura de `studio_info`
- [ ] 🗺️ Validar estructura de `seat_grid`
- [ ] 📈 Validar estructura de `summary`
- [ ] 👥 Validar información de usuarios en asientos reservados
- [ ] ⏰ Validar formato de fechas (ISO 8601)
- [ ] 🎨 Probar renderización visual del mapa
