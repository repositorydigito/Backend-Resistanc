# 🧪 Pruebas de Reserva de Asientos - Ejemplos Prácticos

## 🎯 **URL del Endpoint**
```
POST http://backend-resistanc.test/api/class-schedules/{ID_HORARIO}/reserve-seats
```

---

## 🔧 **Comandos cURL para Probar**

### **1. Reservar un Solo Asiento**
```bash
curl -X POST "http://backend-resistanc.test/api/class-schedules/5/reserve-seats" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -d '{
    "seat_ids": [1]
  }'
```

### **2. Reservar Múltiples Asientos**
```bash
curl -X POST "http://backend-resistanc.test/api/class-schedules/5/reserve-seats" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -d '{
    "seat_ids": [1, 2, 3],
    "minutes_to_expire": 20
  }'
```

### **3. Reservar con Tiempo Personalizado**
```bash
curl -X POST "http://backend-resistanc.test/api/class-schedules/5/reserve-seats" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -d '{
    "seat_ids": [4, 5],
    "minutes_to_expire": 30
  }'
```

### **4. Probar Error - Asiento Inexistente**
```bash
curl -X POST "http://backend-resistanc.test/api/class-schedules/5/reserve-seats" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -d '{
    "seat_ids": [999]
  }'
```

---

## 💻 **Código JavaScript para Probar**

### **Función Completa de Reserva**
```javascript
async function reserveSeats(scheduleId, seatIds, minutesToExpire = 15, token) {
  const url = `http://backend-resistanc.test/api/class-schedules/${scheduleId}/reserve-seats`;
  
  try {
    console.log(`🎯 Reservando asientos ${seatIds.join(', ')} en horario ${scheduleId}...`);
    
    const response = await fetch(url, {
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
      console.error(`❌ Error ${response.status}:`, data.message);
      
      // Manejo específico de errores
      if (response.status === 400 && data.data?.unavailable_seats) {
        console.log('🚫 Asientos no disponibles:', data.data.unavailable_seats);
        console.log('✅ Asientos disponibles:', data.data.available_seats);
      } else if (response.status === 422) {
        console.log('⚠️ Razón:', data.data?.reason);
      }
      
      throw new Error(data.message || `HTTP error! status: ${response.status}`);
    }

    // Mostrar resultado exitoso
    const summary = data.data.reservation_summary;
    console.log('✅ ¡Reserva exitosa!');
    console.log(`📊 Asientos reservados: ${summary.total_reserved}`);
    console.log(`⏰ Expiran en: ${summary.expires_in_minutes} minutos`);
    console.log(`📅 Clase: ${summary.class_name}`);
    console.log(`🏢 Estudio: ${summary.studio_name}`);
    console.log(`📍 Fecha: ${summary.scheduled_date} a las ${summary.start_time}`);
    
    // Mostrar detalles de cada asiento
    console.log('\n🪑 Asientos reservados:');
    data.data.reserved_seats.forEach(seat => {
      console.log(`  - Asiento ${seat.seat_number} (ID: ${seat.seat_id})`);
    });
    
    return data;
  } catch (error) {
    console.error('❌ Error al reservar asientos:', error.message);
    throw error;
  }
}

// Función para probar diferentes escenarios
async function runReservationTests(token) {
  console.log('🧪 Iniciando pruebas de reserva de asientos...\n');

  // Prueba 1: Reservar un asiento
  try {
    console.log('📝 Prueba 1: Reservar un asiento');
    await reserveSeats(5, [1], 15, token);
    console.log('✅ Prueba 1 exitosa\n');
  } catch (error) {
    console.log('❌ Prueba 1 falló\n');
  }

  // Prueba 2: Reservar múltiples asientos
  try {
    console.log('📝 Prueba 2: Reservar múltiples asientos');
    await reserveSeats(5, [2, 3, 4], 20, token);
    console.log('✅ Prueba 2 exitosa\n');
  } catch (error) {
    console.log('❌ Prueba 2 falló\n');
  }

  // Prueba 3: Intentar reservar asiento ya ocupado
  try {
    console.log('📝 Prueba 3: Intentar reservar asiento ocupado');
    await reserveSeats(5, [1], 15, token); // Debería fallar si ya está reservado
    console.log('⚠️ Prueba 3: No debería haber funcionado\n');
  } catch (error) {
    console.log('✅ Prueba 3: Error esperado (asiento ocupado)\n');
  }

  console.log('🎉 Pruebas completadas!');
}

// Ejecutar pruebas
// runReservationTests('tu_token_aqui');
```

### **Función Simplificada**
```javascript
// Función simple para uso rápido
async function quickReserve(scheduleId, seatIds, token) {
  const response = await fetch(`http://backend-resistanc.test/api/class-schedules/${scheduleId}/reserve-seats`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ seat_ids: seatIds })
  });
  
  const data = await response.json();
  
  if (response.ok) {
    console.log(`✅ ${data.data.reservation_summary.total_reserved} asientos reservados`);
    return data;
  } else {
    console.error(`❌ Error: ${data.message}`);
    throw new Error(data.message);
  }
}

// Uso rápido
// quickReserve(5, [1, 2], 'tu_token');
```

---

## 🔍 **Verificación de Resultados**

### **Respuesta Exitosa Esperada:**
```javascript
// Verificar estructura de respuesta
function validateReservationResponse(response) {
  const required = [
    'success',
    'message', 
    'data.reserved_seats',
    'data.reservation_summary'
  ];
  
  required.forEach(path => {
    const value = path.split('.').reduce((obj, key) => obj?.[key], response);
    if (value === undefined) {
      throw new Error(`Campo requerido faltante: ${path}`);
    }
  });
  
  // Verificar que cada asiento tenga la estructura correcta
  response.data.reserved_seats.forEach(seat => {
    if (!seat.seat_id || !seat.seat_number || !seat.status) {
      throw new Error('Estructura de asiento inválida');
    }
  });
  
  console.log('✅ Estructura de respuesta válida');
  return true;
}
```

---

## 📱 **Ejemplo para Frontend**

### **React Hook para Reservas**
```javascript
import { useState } from 'react';

function useReservation() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const reserveSeats = async (scheduleId, seatIds, minutesToExpire = 15) => {
    setLoading(true);
    setError(null);
    
    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch(`/api/class-schedules/${scheduleId}/reserve-seats`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          seat_ids: seatIds,
          minutes_to_expire: minutesToExpire
        })
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message);
      }
      
      return data;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };
  
  return { reserveSeats, loading, error };
}

// Componente de ejemplo
function SeatReservation({ scheduleId, selectedSeats }) {
  const { reserveSeats, loading, error } = useReservation();
  
  const handleReserve = async () => {
    try {
      const result = await reserveSeats(scheduleId, selectedSeats);
      alert(`¡${result.data.reservation_summary.total_reserved} asientos reservados!`);
    } catch (error) {
      alert(`Error: ${error.message}`);
    }
  };
  
  return (
    <div>
      <button onClick={handleReserve} disabled={loading || selectedSeats.length === 0}>
        {loading ? 'Reservando...' : `Reservar ${selectedSeats.length} asientos`}
      </button>
      {error && <p style={{color: 'red'}}>Error: {error}</p>}
    </div>
  );
}
```

---

## 🧪 **Casos de Prueba Específicos**

### **1. Flujo Completo de Reserva**
```javascript
async function testCompleteFlow(token) {
  // 1. Obtener mapa de asientos
  const seatMap = await fetch('http://backend-resistanc.test/api/class-schedules/5/seat-map', {
    headers: { 'Authorization': `Bearer ${token}` }
  }).then(r => r.json());
  
  // 2. Encontrar asientos disponibles
  const availableSeats = [];
  Object.values(seatMap.seat_grid).forEach(row => {
    Object.values(row).forEach(seat => {
      if (seat.exists && seat.status === 'available') {
        availableSeats.push(seat.seat_id);
      }
    });
  });
  
  console.log('Asientos disponibles:', availableSeats);
  
  // 3. Reservar algunos asientos
  if (availableSeats.length > 0) {
    const seatsToReserve = availableSeats.slice(0, 2); // Tomar los primeros 2
    await reserveSeats(5, seatsToReserve, 15, token);
  }
}
```

### **2. Prueba de Límites**
```javascript
async function testLimits(token) {
  // Probar máximo de asientos (10)
  const maxSeats = Array.from({length: 10}, (_, i) => i + 1);
  
  try {
    await reserveSeats(5, maxSeats, 15, token);
    console.log('✅ Reserva de 10 asientos exitosa');
  } catch (error) {
    console.log('❌ Error con 10 asientos:', error.message);
  }
  
  // Probar más del máximo (debería fallar)
  const tooManySeats = Array.from({length: 11}, (_, i) => i + 1);
  
  try {
    await reserveSeats(5, tooManySeats, 15, token);
    console.log('⚠️ No debería permitir más de 10 asientos');
  } catch (error) {
    console.log('✅ Error esperado con 11 asientos:', error.message);
  }
}
```

---

## 📋 **Checklist de Validación**

### **Antes de Probar:**
- [ ] ✅ Tener token de autenticación válido
- [ ] ✅ Conocer ID de horario existente
- [ ] ✅ Verificar que hay asientos disponibles
- [ ] ✅ Confirmar que el horario permite reservas

### **Pruebas a Realizar:**
- [ ] ✅ Reserva exitosa de 1 asiento
- [ ] ✅ Reserva exitosa de múltiples asientos
- [ ] ✅ Reserva con tiempo personalizado
- [ ] ❌ Error con asientos inexistentes
- [ ] ❌ Error con asientos ya ocupados
- [ ] ❌ Error con horario inexistente
- [ ] ❌ Error sin autenticación
- [ ] ❌ Error con datos inválidos

### **Verificaciones Post-Reserva:**
- [ ] ✅ Verificar que los asientos cambiaron a estado "reserved"
- [ ] ✅ Verificar tiempo de expiración correcto
- [ ] ✅ Verificar que el usuario está asignado
- [ ] ✅ Verificar estructura de respuesta completa

---

## 🎉 **¡Listo para Probar!**

Usa estos ejemplos para probar la funcionalidad de reserva de asientos. Recuerda:

1. **Obtener token** haciendo login primero
2. **Verificar asientos disponibles** con el endpoint de seat-map
3. **Reservar asientos** con el nuevo endpoint
4. **Verificar resultado** en el mapa de asientos actualizado

```javascript
// Flujo completo de ejemplo
const token = 'tu_token_aqui';
await reserveSeats(5, [1, 2, 3], 20, token);
```
