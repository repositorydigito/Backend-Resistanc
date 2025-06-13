# 🧪 Pruebas de Filtros de Fecha - API Horarios de Clases

## 🎯 URLs de Prueba Directa

### 📅 **Filtro por Fecha Específica**
```
GET http://backend-resistanc.test/api/class-schedules?scheduled_date=2024-06-15
Authorization: Bearer tu_token_aqui
```

### 📊 **Filtro por Rango de Fechas**
```
GET http://backend-resistanc.test/api/class-schedules?date_from=2024-06-10&date_to=2024-06-20
Authorization: Bearer tu_token_aqui
```

### 🏢 **Filtro por Estudio y Fecha**
```
GET http://backend-resistanc.test/api/class-schedules?studio_id=3&scheduled_date=2024-06-15
Authorization: Bearer tu_token_aqui
```

---

## 🔧 Comandos cURL para Probar

### 1. **Horarios de Hoy**
```bash
curl -X GET "http://backend-resistanc.test/api/class-schedules?scheduled_date=$(date +%Y-%m-%d)" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

### 2. **Horarios de Esta Semana**
```bash
# Desde hoy hasta dentro de 7 días
curl -X GET "http://backend-resistanc.test/api/class-schedules?date_from=$(date +%Y-%m-%d)&date_to=$(date -d '+7 days' +%Y-%m-%d)" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

### 3. **Horarios de Fecha Específica con Contadores**
```bash
curl -X GET "http://backend-resistanc.test/api/class-schedules?scheduled_date=2024-06-15&include_counts=true" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

### 4. **Filtros Múltiples**
```bash
curl -X GET "http://backend-resistanc.test/api/class-schedules?studio_id=3&date_from=2024-06-15&search=Yoga&per_page=10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

---

## 💻 Ejemplos de Código

### JavaScript (Fetch)
```javascript
// Función para obtener horarios por fecha
async function getSchedulesByDate(date, token) {
  try {
    const response = await fetch(`http://backend-resistanc.test/api/class-schedules?scheduled_date=${date}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log('Horarios encontrados:', data.data.length);
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}

// Función para obtener horarios por rango de fechas
async function getSchedulesByDateRange(dateFrom, dateTo, token) {
  const params = new URLSearchParams({
    date_from: dateFrom,
    date_to: dateTo,
    include_counts: 'true'
  });

  try {
    const response = await fetch(`http://backend-resistanc.test/api/class-schedules?${params}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error:', error);
    throw error;
  }
}

// Ejemplos de uso
const token = 'tu_token_aqui';

// Horarios de hoy
const today = new Date().toISOString().split('T')[0];
getSchedulesByDate(today, token);

// Horarios de esta semana
const nextWeek = new Date();
nextWeek.setDate(nextWeek.getDate() + 7);
getSchedulesByDateRange(today, nextWeek.toISOString().split('T')[0], token);
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

// Obtener horarios por fecha específica
async function getSchedulesByDate(date) {
  try {
    const response = await api.get('/class-schedules', {
      params: { scheduled_date: date }
    });
    return response.data;
  } catch (error) {
    console.error('Error fetching schedules:', error.response?.data || error.message);
    throw error;
  }
}

// Obtener horarios con filtros múltiples
async function getFilteredSchedules(filters) {
  try {
    const response = await api.get('/class-schedules', { params: filters });
    return response.data;
  } catch (error) {
    console.error('Error fetching filtered schedules:', error.response?.data || error.message);
    throw error;
  }
}

// Ejemplos de uso
getSchedulesByDate('2024-06-15');

getFilteredSchedules({
  studio_id: 3,
  date_from: '2024-06-15',
  date_to: '2024-06-20',
  include_counts: true
});
```

### PHP (Guzzle)
```php
<?php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ClassScheduleService {
    private $client;
    
    public function __construct($token) {
        $this->client = new Client([
            'base_uri' => 'http://backend-resistanc.test/api/',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$token}"
            ]
        ]);
    }
    
    public function getSchedulesByDate($date) {
        try {
            $response = $this->client->get('class-schedules', [
                'query' => ['scheduled_date' => $date]
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new Exception('Error fetching schedules: ' . $e->getMessage());
        }
    }
    
    public function getSchedulesByDateRange($dateFrom, $dateTo, $additionalFilters = []) {
        $query = array_merge([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'include_counts' => true
        ], $additionalFilters);
        
        try {
            $response = $this->client->get('class-schedules', ['query' => $query]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new Exception('Error fetching schedules: ' . $e->getMessage());
        }
    }
    
    public function getSchedulesWithFilters($filters) {
        try {
            $response = $this->client->get('class-schedules', ['query' => $filters]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new Exception('Error fetching schedules: ' . $e->getMessage());
        }
    }
}

// Ejemplos de uso
$service = new ClassScheduleService('tu_token_aqui');

// Horarios de hoy
$today = date('Y-m-d');
$todaySchedules = $service->getSchedulesByDate($today);
echo "Horarios de hoy: " . count($todaySchedules['data']) . "\n";

// Horarios de esta semana
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$weekSchedules = $service->getSchedulesByDateRange($today, $nextWeek);
echo "Horarios de esta semana: " . count($weekSchedules['data']) . "\n";

// Horarios con filtros múltiples
$filteredSchedules = $service->getSchedulesWithFilters([
    'studio_id' => 3,
    'date_from' => '2024-06-15',
    'search' => 'Yoga',
    'per_page' => 20
]);
```

### Python (Requests)
```python
import requests
from datetime import datetime, timedelta

class ClassScheduleAPI:
    def __init__(self, base_url, token):
        self.base_url = base_url
        self.headers = {
            'Accept': 'application/json',
            'Authorization': f'Bearer {token}'
        }
    
    def get_schedules_by_date(self, date):
        """Obtener horarios por fecha específica"""
        params = {'scheduled_date': date}
        response = requests.get(f'{self.base_url}/api/class-schedules', 
                              headers=self.headers, params=params)
        response.raise_for_status()
        return response.json()
    
    def get_schedules_by_date_range(self, date_from, date_to, **kwargs):
        """Obtener horarios por rango de fechas"""
        params = {
            'date_from': date_from,
            'date_to': date_to,
            'include_counts': True,
            **kwargs
        }
        response = requests.get(f'{self.base_url}/api/class-schedules', 
                              headers=self.headers, params=params)
        response.raise_for_status()
        return response.json()
    
    def get_filtered_schedules(self, **filters):
        """Obtener horarios con filtros personalizados"""
        response = requests.get(f'{self.base_url}/api/class-schedules', 
                              headers=self.headers, params=filters)
        response.raise_for_status()
        return response.json()

# Ejemplos de uso
api = ClassScheduleAPI('http://backend-resistanc.test', 'tu_token_aqui')

# Horarios de hoy
today = datetime.now().strftime('%Y-%m-%d')
today_schedules = api.get_schedules_by_date(today)
print(f"Horarios de hoy: {len(today_schedules['data'])}")

# Horarios de esta semana
next_week = (datetime.now() + timedelta(days=7)).strftime('%Y-%m-%d')
week_schedules = api.get_schedules_by_date_range(today, next_week)
print(f"Horarios de esta semana: {len(week_schedules['data'])}")

# Horarios con filtros múltiples
filtered_schedules = api.get_filtered_schedules(
    studio_id=3,
    date_from='2024-06-15',
    search='Yoga',
    per_page=20
)
```

---

## 🧪 Casos de Prueba Específicos

### 1. **Validar Formato de Fecha**
```bash
# Fecha válida
curl -X GET "http://backend-resistanc.test/api/class-schedules?scheduled_date=2024-06-15" \
  -H "Authorization: Bearer TU_TOKEN"

# Fecha inválida (debería dar error 422)
curl -X GET "http://backend-resistanc.test/api/class-schedules?scheduled_date=15-06-2024" \
  -H "Authorization: Bearer TU_TOKEN"
```

### 2. **Probar Rango de Fechas**
```bash
# Rango válido
curl -X GET "http://backend-resistanc.test/api/class-schedules?date_from=2024-06-10&date_to=2024-06-20" \
  -H "Authorization: Bearer TU_TOKEN"

# Rango inválido (fecha_desde > fecha_hasta)
curl -X GET "http://backend-resistanc.test/api/class-schedules?date_from=2024-06-20&date_to=2024-06-10" \
  -H "Authorization: Bearer TU_TOKEN"
```

### 3. **Combinar Filtros**
```bash
# Estudio + Fecha + Búsqueda
curl -X GET "http://backend-resistanc.test/api/class-schedules?studio_id=3&scheduled_date=2024-06-15&search=Yoga" \
  -H "Authorization: Bearer TU_TOKEN"

# Instructor + Rango de fechas + Paginación
curl -X GET "http://backend-resistanc.test/api/class-schedules?instructor_id=2&date_from=2024-06-15&date_to=2024-06-20&per_page=5" \
  -H "Authorization: Bearer TU_TOKEN"
```

---

## 📊 Validación de Respuestas

### Estructura Esperada
```javascript
// Validar que la respuesta tenga la estructura correcta
function validateScheduleResponse(response) {
  // Verificar estructura principal
  if (!response.data || !Array.isArray(response.data)) {
    throw new Error('Invalid response structure');
  }
  
  // Verificar cada horario
  response.data.forEach(schedule => {
    if (!schedule.id || !schedule.scheduled_date || !schedule.start_time) {
      throw new Error('Invalid schedule structure');
    }
    
    // Verificar formato de fecha
    if (!/^\d{4}-\d{2}-\d{2}$/.test(schedule.scheduled_date)) {
      throw new Error('Invalid date format');
    }
  });
  
  return true;
}
```

### Verificar Filtros
```javascript
// Verificar que los filtros funcionan correctamente
function verifyDateFilter(schedules, expectedDate) {
  return schedules.data.every(schedule => 
    schedule.scheduled_date === expectedDate
  );
}

function verifyDateRangeFilter(schedules, dateFrom, dateTo) {
  return schedules.data.every(schedule => 
    schedule.scheduled_date >= dateFrom && 
    schedule.scheduled_date <= dateTo
  );
}
```

---

## 🚨 Manejo de Errores Comunes

### Error 422 - Formato de Fecha Inválido
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "scheduled_date": ["The scheduled date does not match the format Y-m-d."]
  }
}
```

### Error 401 - Token Inválido
```json
{
  "message": "Unauthenticated."
}
```

### Respuesta Vacía (Sin Horarios)
```json
{
  "data": [],
  "links": {...},
  "meta": {
    "total": 0
  }
}
```

---

## 📋 Checklist de Pruebas

- [ ] ✅ Filtro por fecha específica funciona
- [ ] ✅ Filtro por rango de fechas funciona  
- [ ] ✅ Combinación de filtros funciona
- [ ] ❌ Error con formato de fecha inválido
- [ ] 🔐 Error con token inválido
- [ ] 📊 Paginación funciona correctamente
- [ ] 🔍 Búsqueda por texto funciona
- [ ] 📈 Contadores se incluyen cuando se solicitan
- [ ] 🏢 Filtros por estudio/instructor funcionan
- [ ] ⏰ Ordenamiento por fecha y hora es correcto
