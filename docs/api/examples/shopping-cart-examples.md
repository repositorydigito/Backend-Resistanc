# Ejemplos de Uso - API del Carrito de Compras

## Configuración Inicial

### 1. Autenticación
```bash
# Login para obtener token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@ejemplo.com",
    "password": "password123"
  }'

# Respuesta
{
  "exito": true,
  "codMensaje": 0,
  "mensajeUsuario": "Login exitoso",
  "datoAdicional": {
    "token": "1|abc123def456...",
    "user": { ... }
  }
}
```

### 2. Configurar Token para Requests
```bash
# Guardar token en variable
TOKEN="1|abc123def456..."

# Usar en headers
curl -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  http://localhost:8000/api/shopping-cart/
```

## Ejemplos de Uso

### Ejemplo 1: Flujo Completo de Compra

#### Paso 1: Verificar Carrito Actual
```bash
curl -X GET http://localhost:8000/api/shopping-cart/ \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

**Respuesta:**
```json
{
  "exito": true,
  "codMensaje": 0,
  "mensajeUsuario": "Carrito obtenido exitosamente",
  "datoAdicional": {
    "cart": {
      "id": 1,
      "subtotal": 0.00,
      "tax_amount": 0.00,
      "total_amount": 0.00,
      "currency": "PEN",
      "total_items": 0,
      "is_empty": true,
      "expires_at": "2025-01-14T15:30:00.000000Z"
    },
    "items": []
  }
}
```

#### Paso 2: Agregar Producto Simple
```bash
curl -X POST http://localhost:8000/api/shopping-cart/add \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 2
  }'
```

**Respuesta:**
```json
{
  "exito": true,
  "codMensaje": 0,
  "mensajeUsuario": "Producto agregado al carrito exitosamente",
  "datoAdicional": {
    "cart_item": {
      "id": 1,
      "quantity": 2,
      "unit_price": 50.00,
      "total_price": 100.00
    },
    "cart_total": 118.00,
    "cart_items_count": 2
  }
}
```

#### Paso 3: Agregar Producto con Variante
```bash
curl -X POST http://localhost:8000/api/shopping-cart/add \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 2,
    "quantity": 1,
    "product_variant_id": 5
  }'
```

#### Paso 4: Actualizar Cantidad
```bash
curl -X PUT http://localhost:8000/api/shopping-cart/update-quantity \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cart_item_id": 1,
    "quantity": 3
  }'
```

#### Paso 5: Verificar Carrito Actualizado
```bash
curl -X GET http://localhost:8000/api/shopping-cart/ \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

**Respuesta:**
```json
{
  "exito": true,
  "codMensaje": 0,
  "mensajeUsuario": "Carrito obtenido exitosamente",
  "datoAdicional": {
    "cart": {
      "id": 1,
      "subtotal": 200.00,
      "tax_amount": 36.00,
      "total_amount": 236.00,
      "currency": "PEN",
      "total_items": 4,
      "is_empty": false,
      "expires_at": "2025-01-14T15:30:00.000000Z"
    },
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_variant_id": null,
        "quantity": 3,
        "unit_price": 50.00,
        "total_price": 150.00,
        "product": {
          "id": 1,
          "name": "Camiseta Resistance",
          "sku": "CAM-001",
          "img_url": "products/main/camisa.jpg"
        },
        "variant": null
      },
      {
        "id": 2,
        "product_id": 2,
        "product_variant_id": 5,
        "quantity": 1,
        "unit_price": 50.00,
        "total_price": 50.00,
        "product": {
          "id": 2,
          "name": "Pantalón Deportivo",
          "sku": "PAN-001",
          "img_url": "products/main/pantalon.jpg"
        },
        "variant": {
          "id": 5,
          "name": "Talla M - Azul",
          "sku": "PAN-001-M-AZUL"
        }
      }
    ]
  }
}
```

#### Paso 6: Confirmar Compra
```bash
curl -X POST http://localhost:8000/api/shopping-cart/confirm \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

**Respuesta:**
```json
{
  "exito": true,
  "codMensaje": 0,
  "mensajeUsuario": "Orden creada exitosamente",
  "datoAdicional": {
    "order": {
      "id": 1,
      "order_number": "RST-2025-000001",
      "total_amount": 236.00,
      "status": "pending"
    },
    "new_cart": {
      "id": 2,
      "is_empty": true,
      "total_items": 0
    }
  }
}
```

### Ejemplo 2: Gestión de Errores

#### Error: Stock Insuficiente
```bash
curl -X POST http://localhost:8000/api/shopping-cart/add \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 999
  }'
```

**Respuesta:**
```json
{
  "exito": false,
  "codMensaje": 1,
  "mensajeUsuario": "Error al agregar el producto al carrito",
  "datoAdicional": "Stock insuficiente para este producto"
}
```

#### Error: Producto No Encontrado
```bash
curl -X POST http://localhost:8000/api/shopping-cart/add \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 999,
    "quantity": 1
  }'
```

**Respuesta:**
```json
{
  "exito": false,
  "codMensaje": 1,
  "mensajeUsuario": "Error al agregar el producto al carrito",
  "datoAdicional": "No query results for model [App\\Models\\Product] 999"
}
```

### Ejemplo 3: Limpiar Carrito

```bash
curl -X DELETE http://localhost:8000/api/shopping-cart/clear \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

**Respuesta:**
```json
{
  "exito": true,
  "codMensaje": 0,
  "mensajeUsuario": "Carrito limpiado exitosamente",
  "datoAdicional": {
    "cart_total": 0.00,
    "cart_items_count": 0,
    "is_empty": true
  }
}
```

### Ejemplo 4: Eliminar Producto Específico

```bash
curl -X POST http://localhost:8000/api/shopping-cart/remove \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cart_item_id": 1
  }'
```

**Respuesta:**
```json
{
  "exito": true,
  "codMensaje": 0,
  "mensajeUsuario": "Producto eliminado del carrito exitosamente",
  "datoAdicional": {
    "cart_total": 118.00,
    "cart_items_count": 2,
    "is_empty": false
  }
}
```

## Casos de Uso Comunes

### 1. Verificar Stock Antes de Agregar
```javascript
// Frontend - Verificar stock antes de agregar al carrito
async function addToCart(productId, quantity, variantId = null) {
  try {
    const response = await fetch('/api/shopping-cart/add', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        product_id: productId,
        quantity: quantity,
        product_variant_id: variantId
      })
    });
    
    const data = await response.json();
    
    if (data.exito) {
      showSuccess('Producto agregado al carrito');
      updateCartCount(data.datoAdicional.cart_items_count);
    } else {
      showError(data.mensajeUsuario);
    }
  } catch (error) {
    showError('Error al agregar producto');
  }
}
```

### 2. Actualizar Cantidad con Validación
```javascript
// Frontend - Actualizar cantidad con validación de stock
async function updateQuantity(cartItemId, newQuantity) {
  if (newQuantity < 1) {
    showError('La cantidad debe ser mayor a 0');
    return;
  }
  
  try {
    const response = await fetch('/api/shopping-cart/update-quantity', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        cart_item_id: cartItemId,
        quantity: newQuantity
      })
    });
    
    const data = await response.json();
    
    if (data.exito) {
      updateCartTotal(data.datoAdicional.cart_total);
      updateItemTotal(cartItemId, data.datoAdicional.cart_item.total_price);
    } else {
      showError(data.mensajeUsuario);
    }
  } catch (error) {
    showError('Error al actualizar cantidad');
  }
}
```

### 3. Proceso de Checkout
```javascript
// Frontend - Proceso completo de checkout
async function checkout() {
  try {
    // 1. Verificar carrito no vacío
    const cartResponse = await fetch('/api/shopping-cart/', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const cartData = await cartResponse.json();
    
    if (cartData.datoAdicional.cart.is_empty) {
      showError('El carrito está vacío');
      return;
    }
    
    // 2. Confirmar compra
    const confirmResponse = await fetch('/api/shopping-cart/confirm', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const confirmData = await confirmResponse.json();
    
    if (confirmData.exito) {
      showSuccess('Orden creada exitosamente');
      // Redirigir a página de pago o confirmación
      window.location.href = `/orders/${confirmData.datoAdicional.order.id}`;
    } else {
      showError(confirmData.mensajeUsuario);
    }
  } catch (error) {
    showError('Error en el proceso de checkout');
  }
}
```

## Notas Importantes

1. **Autenticación**: Todas las rutas requieren autenticación con Sanctum
2. **Carrito Temporal**: El carrito se mantiene hasta confirmar la compra
3. **Nuevo Carrito**: Después de confirmar, se crea automáticamente un nuevo carrito vacío
4. **Stock**: Se valida el stock disponible antes de agregar productos
5. **Precios**: Se calcula automáticamente IGV (18%) sobre el subtotal
6. **Expiración**: Los carritos expiran después de 7 días 