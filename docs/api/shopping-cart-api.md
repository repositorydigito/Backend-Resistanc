# API del Carrito de Compras

Esta API permite gestionar el carrito de compras de usuarios autenticados. El carrito es temporal y se mantiene hasta que se confirme la compra, momento en el cual se crea un nuevo carrito vacío para el usuario.

## Autenticación

Todas las rutas requieren autenticación mediante Sanctum. Incluye el token Bearer en el header:

```
Authorization: Bearer {token}
```

## Endpoints

### 1. Mostrar Carrito

**GET** `/api/shopping-cart/`

Muestra todos los productos en el carrito del usuario autenticado.

**Respuesta exitosa:**
```json
{
    "exito": true,
    "codMensaje": 0,
    "mensajeUsuario": "Carrito obtenido exitosamente",
    "datoAdicional": {
        "cart": {
            "id": 1,
            "subtotal": 150.00,
            "tax_amount": 27.00,
            "total_amount": 177.00,
            "currency": "PEN",
            "total_items": 3,
            "is_empty": false,
            "expires_at": "2025-01-14T15:30:00.000000Z"
        },
        "items": [
            {
                "id": 1,
                "product_id": 1,
                "product_variant_id": null,
                "quantity": 2,
                "unit_price": 50.00,
                "total_price": 100.00,
                "product": {
                    "id": 1,
                    "name": "Producto Ejemplo",
                    "sku": "PROD-001",
                    "img_url": "products/main/producto.jpg"
                },
                "variant": null
            }
        ]
    }
}
```

### 2. Agregar Producto al Carrito

**POST** `/api/shopping-cart/add`

Agrega un producto al carrito del usuario.

**Parámetros:**
```json
{
    "product_id": 1,
    "quantity": 2,
    "product_variant_id": null
}
```

**Respuesta exitosa:**
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
        "cart_total": 177.00,
        "cart_items_count": 3
    }
}
```

### 3. Eliminar Producto del Carrito

**POST** `/api/shopping-cart/remove`

Elimina un producto específico del carrito.

**Parámetros:**
```json
{
    "cart_item_id": 1
}
```

**Respuesta exitosa:**
```json
{
    "exito": true,
    "codMensaje": 0,
    "mensajeUsuario": "Producto eliminado del carrito exitosamente",
    "datoAdicional": {
        "cart_total": 77.00,
        "cart_items_count": 2,
        "is_empty": false
    }
}
```

### 4. Actualizar Cantidad

**PUT** `/api/shopping-cart/update-quantity`

Actualiza la cantidad de un producto en el carrito.

**Parámetros:**
```json
{
    "cart_item_id": 1,
    "quantity": 3
}
```

**Respuesta exitosa:**
```json
{
    "exito": true,
    "codMensaje": 0,
    "mensajeUsuario": "Cantidad actualizada exitosamente",
    "datoAdicional": {
        "cart_item": {
            "id": 1,
            "quantity": 3,
            "total_price": 150.00
        },
        "cart_total": 227.00
    }
}
```

### 5. Limpiar Carrito

**DELETE** `/api/shopping-cart/clear`

Elimina todos los productos del carrito.

**Respuesta exitosa:**
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

### 6. Confirmar Carrito

**POST** `/api/shopping-cart/confirm`

Confirma el carrito actual, crea una orden y genera un nuevo carrito vacío.

**Respuesta exitosa:**
```json
{
    "exito": true,
    "codMensaje": 0,
    "mensajeUsuario": "Orden creada exitosamente",
    "datoAdicional": {
        "order": {
            "id": 1,
            "order_number": "RST-2025-000001",
            "total_amount": 177.00,
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

## Códigos de Error

### Error de Autenticación
```json
{
    "exito": false,
    "codMensaje": 1,
    "mensajeUsuario": "Error al mostrar los productos del carrito",
    "datoAdicional": "Usuario no autenticado"
}
```

### Error de Stock Insuficiente
```json
{
    "exito": false,
    "codMensaje": 1,
    "mensajeUsuario": "Error al agregar el producto al carrito",
    "datoAdicional": "Stock insuficiente para este producto"
}
```

### Error de Carrito Vacío
```json
{
    "exito": false,
    "codMensaje": 1,
    "mensajeUsuario": "Error al confirmar el carrito",
    "datoAdicional": "No puedes confirmar un carrito vacío"
}
```

## Características del Sistema

### Carrito Temporal
- Cada usuario tiene un carrito activo que se mantiene hasta confirmar la compra
- El carrito expira automáticamente después de 7 días
- Al confirmar una compra, se crea automáticamente un nuevo carrito vacío

### Gestión de Stock
- Se verifica el stock disponible antes de agregar productos
- Soporte para productos con y sin variantes
- Validación de stock en tiempo real

### Cálculo de Precios
- Subtotal: Suma de todos los productos
- IGV: 18% del subtotal
- Total: Subtotal + IGV

### Productos y Variantes
- Soporte para productos simples y con variantes
- Precios específicos por variante
- Validación de que las variantes pertenezcan al producto correcto

## Flujo de Uso Típico

1. **Usuario se autentica** y obtiene un token
2. **Consulta su carrito** actual (GET `/api/shopping-cart/`)
3. **Agrega productos** al carrito (POST `/api/shopping-cart/add`)
4. **Actualiza cantidades** si es necesario (PUT `/api/shopping-cart/update-quantity`)
5. **Elimina productos** si es necesario (POST `/api/shopping-cart/remove`)
6. **Confirma la compra** (POST `/api/shopping-cart/confirm`)
7. **Se crea automáticamente** un nuevo carrito vacío para futuras compras 