# 📅 API de Horarios de Clases - Lista Completa

## 📋 Descripción General

Esta API permite obtener una lista de horarios de clases programados con múltiples opciones de filtrado, incluyendo filtros por fecha, clase, instructor, estudio y disciplina.

---

## 🔗 Endpoint

```http
GET /api/class-schedules
```

### 🔐 Autenticación Requerida
```http
Authorization: Bearer {tu_token_aqui}
```

---

## 📝 Parámetros de Consulta

### 📊 **Paginación**
| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `per_page` | integer | Número de horarios por página (máximo 50). Si no se especifica, devuelve todos los resultados | `15` |
| `page` | integer | Número de página para la paginación | `1` |

### 🔍 **Filtros por ID**
| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `class_id` | integer | Filtrar por ID de clase específica | `1` |
| `instructor_id` | integer | Filtrar por ID de instructor específico | `2` |
| `studio_id` | integer | Filtrar por ID de estudio específico | `3` |
| `discipline_id` | integer | Filtrar por ID de disciplina específica | `1` |

### 📅 **Filtros de Fecha** ⭐
| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `scheduled_date` | string | Filtrar por fecha específica (YYYY-MM-DD) | `"2024-06-15"` |
| `date_from` | string | Filtrar desde una fecha específica (YYYY-MM-DD) | `"2024-06-10"` |
| `date_to` | string | Filtrar hasta una fecha específica (YYYY-MM-DD) | `"2024-06-20"` |

### 🔎 **Búsqueda y Opciones**
| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `search` | string | Buscar por nombre de clase | `"Yoga"` |
| `include_counts` | boolean | Incluir contadores de reservas y asientos | `true` |
| `include_relations` | boolean | Incluir información completa de relaciones | `true` |

---

## 📤 Ejemplos de Solicitudes

### 1. **Filtrar por Fecha Específica**
```bash
curl -X GET "http://backend-resistanc.test/api/class-schedules?scheduled_date=2024-06-15" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer tu_token_aqui"
```

### 2. **Filtrar por Rango de Fechas**
```bash
curl -X GET "http://backend-resistanc.test/api/class-schedules?date_from=2024-06-10&date_to=2024-06-20" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer tu_token_aqui"
```

### 3. **Filtrar por Estudio y Fecha**
```bash
curl -X GET "http://backend-resistanc.test/api/class-schedules?studio_id=3&date_from=2024-06-15" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer tu_token_aqui"
```

### 4. **Búsqueda con Paginación**
```bash
curl -X GET "http://backend-resistanc.test/api/class-schedules?search=Yoga&per_page=10&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer tu_token_aqui"
```

### 5. **Filtros Múltiples Combinados**
```bash
curl -X GET "http://backend-resistanc.test/api/class-schedules?instructor_id=2&discipline_id=1&date_from=2024-06-15&include_counts=true" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer tu_token_aqui"
```

---

## 📥 Respuesta Exitosa (200)

```json
{
  "data": [
    {
      "id": 1,
      "scheduled_date": "2024-06-15",
      "start_time": "08:00:00",
      "end_time": "09:00:00",
      "status": "scheduled",
      "max_capacity": 20,
      "current_reservations": 15,
      "available_spots": 5,
      "notes": "Traer mat de yoga",
      "class": {
        "id": 1,
        "name": "Hatha Yoga",
        "duration_minutes": 60,
        "difficulty_level": "beginner"
      },
      "instructor": {
        "id": 2,
        "name": "Ana López",
        "profile_image": "/images/instructors/ana.jpg"
      },
      "studio": {
        "id": 3,
        "name": "Sala Yoga A",
        "max_capacity": 20
      },
      "seats_count": 15,
      "reservations_count": 15,
      "created_at": "2024-01-15T10:30:00.000Z",
      "updated_at": "2024-01-15T10:30:00.000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/class-schedules?page=1",
    "last": "http://localhost/api/class-schedules?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost/api/class-schedules",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

---

## 🎯 Casos de Uso Comunes

### 📅 **Filtros de Fecha**

#### **Horarios de Hoy**
```
GET /api/class-schedules?scheduled_date=2024-06-15
```

#### **Horarios de Esta Semana**
```
GET /api/class-schedules?date_from=2024-06-10&date_to=2024-06-16
```

#### **Horarios del Próximo Mes**
```
GET /api/class-schedules?date_from=2024-07-01&date_to=2024-07-31
```

### 🏢 **Filtros por Estudio**

#### **Horarios de un Estudio Específico**
```
GET /api/class-schedules?studio_id=3
```

#### **Horarios de un Estudio en Fecha Específica**
```
GET /api/class-schedules?studio_id=3&scheduled_date=2024-06-15
```

### 👨‍🏫 **Filtros por Instructor**

#### **Horarios de un Instructor**
```
GET /api/class-schedules?instructor_id=2
```

#### **Horarios de un Instructor Esta Semana**
```
GET /api/class-schedules?instructor_id=2&date_from=2024-06-10&date_to=2024-06-16
```

### 🏃‍♀️ **Filtros por Disciplina**

#### **Clases de Yoga**
```
GET /api/class-schedules?discipline_id=1
```

#### **Clases de Cycling**
```
GET /api/class-schedules?discipline_id=2
```

---

## 🔍 Ejemplos JavaScript

### **Filtrar por Fecha con Fetch**
```javascript
async function getSchedulesByDate(date, token) {
  const response = await fetch(`http://backend-resistanc.test/api/class-schedules?scheduled_date=${date}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Uso
const schedules = await getSchedulesByDate('2024-06-15', 'tu_token');
```

### **Filtrar por Rango de Fechas**
```javascript
async function getSchedulesByDateRange(dateFrom, dateTo, token) {
  const params = new URLSearchParams({
    date_from: dateFrom,
    date_to: dateTo,
    include_counts: 'true'
  });
  
  const response = await fetch(`http://backend-resistanc.test/api/class-schedules?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Uso
const schedules = await getSchedulesByDateRange('2024-06-10', '2024-06-20', 'tu_token');
```

### **Filtros Múltiples**
```javascript
async function getFilteredSchedules(filters, token) {
  const params = new URLSearchParams();
  
  // Agregar filtros dinámicamente
  Object.keys(filters).forEach(key => {
    if (filters[key] !== null && filters[key] !== undefined) {
      params.append(key, filters[key]);
    }
  });
  
  const response = await fetch(`http://backend-resistanc.test/api/class-schedules?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Uso
const schedules = await getFilteredSchedules({
  studio_id: 3,
  date_from: '2024-06-15',
  search: 'Yoga',
  per_page: 20
}, 'tu_token');
```

---

## 📱 Ejemplo PHP

```php
<?php
use GuzzleHttp\Client;

class ClassScheduleAPI {
    private $client;
    private $token;
    
    public function __construct($token) {
        $this->token = $token;
        $this->client = new Client([
            'base_uri' => 'http://backend-resistanc.test/api/',
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json'
            ]
        ]);
    }
    
    public function getSchedulesByDate($date) {
        $response = $this->client->get('class-schedules', [
            'query' => ['scheduled_date' => $date]
        ]);
        
        return json_decode($response->getBody(), true);
    }
    
    public function getSchedulesByDateRange($dateFrom, $dateTo) {
        $response = $this->client->get('class-schedules', [
            'query' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'include_counts' => true
            ]
        ]);
        
        return json_decode($response->getBody(), true);
    }
}

// Uso
$api = new ClassScheduleAPI('tu_token_aqui');
$schedules = $api->getSchedulesByDate('2024-06-15');
```

---

## ❌ Respuestas de Error

### 401 - No autenticado
```json
{
  "message": "Unauthenticated."
}
```

### 422 - Parámetros inválidos
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "scheduled_date": ["The scheduled date does not match the format Y-m-d."]
  }
}
```

---

## 💡 Notas Importantes

1. **Formato de Fecha**: Usar formato `YYYY-MM-DD` (ISO 8601)
2. **Filtros Combinables**: Todos los filtros se pueden combinar
3. **Paginación Automática**: Si no especificas `per_page`, devuelve todos los resultados
4. **Ordenamiento**: Los resultados se ordenan por fecha y hora automáticamente
5. **Solo Futuros**: Por defecto solo muestra horarios futuros y con estado "scheduled"

---

## 🧪 Pruebas Rápidas

### Postman Collection
```json
{
  "info": { "name": "Class Schedules API" },
  "item": [
    {
      "name": "Get Schedules by Date",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/api/class-schedules?scheduled_date={{date}}",
        "header": [
          { "key": "Authorization", "value": "Bearer {{token}}" }
        ]
      }
    }
  ]
}
```

### Variables de Entorno
- `base_url`: `http://backend-resistanc.test`
- `token`: `tu_token_de_autenticacion`
- `date`: `2024-06-15`
