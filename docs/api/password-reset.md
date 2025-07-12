# API de Recuperación de Contraseña

Este documento describe los endpoints disponibles para la recuperación de contraseña mediante códigos de 4 dígitos enviados por email.

## Flujo de Recuperación

1. **Enviar código**: El usuario solicita un código de recuperación enviando su email
2. **Verificar código**: El usuario verifica el código recibido por email
3. **Restablecer contraseña**: El usuario establece una nueva contraseña con el código verificado

## Endpoints

### 1. Enviar Código de Recuperación

**POST** `/api/auth/send-reset-code`

Envía un código de 4 dígitos al correo electrónico del usuario.

#### Parámetros

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `email` | string | Sí | Correo electrónico del usuario |

#### Ejemplo de Request

```json
{
  "email": "usuario@ejemplo.com"
}
```

#### Ejemplo de Response (200)

```json
{
  "message": "Se ha enviado un código de verificación a tu correo electrónico.",
  "data": {
    "email": "usuario@ejemplo.com",
    "expires_in": 600
  }
}
```

#### Ejemplo de Response (404)

```json
{
  "message": "No se encontró un usuario con ese correo electrónico."
}
```

#### Ejemplo de Response (429)

```json
{
  "message": "Demasiadas solicitudes. Intenta de nuevo en 60 segundos."
}
```

### 2. Verificar Código de Recuperación

**POST** `/api/auth/verify-reset-code`

Verifica que el código de 4 dígitos enviado al correo sea válido.

#### Parámetros

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `email` | string | Sí | Correo electrónico del usuario |
| `code` | string | Sí | Código de verificación de 4 dígitos |

#### Ejemplo de Request

```json
{
  "email": "usuario@ejemplo.com",
  "code": "1234"
}
```

#### Ejemplo de Response (200)

```json
{
  "message": "Código verificado correctamente.",
  "data": {
    "email": "usuario@ejemplo.com",
    "verified": true
  }
}
```

#### Ejemplo de Response (400)

```json
{
  "message": "Código inválido o expirado."
}
```

### 3. Restablecer Contraseña

**POST** `/api/auth/reset-password`

Cambia la contraseña del usuario usando el código de verificación.

#### Parámetros

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `email` | string | Sí | Correo electrónico del usuario |
| `code` | string | Sí | Código de verificación de 4 dígitos |
| `password` | string | Sí | Nueva contraseña del usuario |
| `password_confirmation` | string | Sí | Confirmación de la nueva contraseña |

#### Reglas de Validación de Contraseña

- Mínimo 8 caracteres
- Debe contener letras mayúsculas y minúsculas
- Debe contener números
- Debe contener símbolos
- No debe estar comprometida (según base de datos de contraseñas comunes)

#### Ejemplo de Request

```json
{
  "email": "usuario@ejemplo.com",
  "code": "1234",
  "password": "NuevaPassword123!",
  "password_confirmation": "NuevaPassword123!"
}
```

#### Ejemplo de Response (200)

```json
{
  "message": "Contraseña restablecida correctamente.",
  "data": {
    "email": "usuario@ejemplo.com",
    "updated_at": "2024-01-15T10:30:00.000Z"
  }
}
```

#### Ejemplo de Response (400)

```json
{
  "message": "Código inválido o expirado."
}
```

## Características de Seguridad

### Rate Limiting

- **Envío de códigos**: Máximo 3 intentos por IP en 60 segundos
- **Verificación de códigos**: Sin límite específico
- **Restablecimiento de contraseña**: Sin límite específico

### Expiración de Códigos

- Los códigos expiran automáticamente después de **10 minutos**
- Un nuevo código invalida automáticamente los códigos anteriores del mismo email
- Los códigos solo pueden usarse una vez

### Limpieza Automática

- Los códigos expirados se eliminan automáticamente después de 1 día
- Se puede ejecutar manualmente: `php artisan password-reset:clean-expired`

## Ejemplo de Uso Completo

### 1. Solicitar código

```bash
curl -X POST http://localhost:8000/api/auth/send-reset-code \
  -H "Content-Type: application/json" \
  -d '{"email": "usuario@ejemplo.com"}'
```

### 2. Verificar código

```bash
curl -X POST http://localhost:8000/api/auth/verify-reset-code \
  -H "Content-Type: application/json" \
  -d '{"email": "usuario@ejemplo.com", "code": "1234"}'
```

### 3. Restablecer contraseña

```bash
curl -X POST http://localhost:8000/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@ejemplo.com",
    "code": "1234",
    "password": "NuevaPassword123!",
    "password_confirmation": "NuevaPassword123!"
  }'
```

## Códigos de Error

| Código | Descripción |
|--------|-------------|
| 200 | Operación exitosa |
| 400 | Código inválido o expirado |
| 404 | Usuario no encontrado |
| 422 | Errores de validación |
| 429 | Demasiadas solicitudes |

## Notas Importantes

1. **Tokens de API**: Al restablecer la contraseña, todos los tokens de API del usuario son revocados automáticamente
2. **Email**: El usuario debe volver a iniciar sesión después de restablecer su contraseña
3. **Logs**: Todas las operaciones de recuperación de contraseña son registradas en los logs del sistema
4. **Notificaciones**: Los códigos se envían por email usando el sistema de notificaciones de Laravel 
