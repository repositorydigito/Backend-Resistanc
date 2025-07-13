# Flujo de Recuperación de Contraseña

## 📋 Resumen

El sistema de recuperación de contraseña utiliza códigos de 4 dígitos enviados por correo electrónico, con un flujo de 3 pasos:

1. **Enviar código** - Solicitar código de recuperación
2. **Verificar código** - Validar el código recibido
3. **Restablecer contraseña** - Cambiar contraseña con código verificado

## 🔧 Configuración Requerida

### Variables de Entorno (.env)

```env
# Configuración de correo
MAIL_MAILER=smtp
MAIL_HOST=tu-servidor-smtp.com
MAIL_PORT=587
MAIL_USERNAME=tu-usuario@dominio.com
MAIL_PASSWORD=tu-contraseña
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.com
MAIL_FROM_NAME="Tu Aplicación"

# URL de la aplicación
APP_URL=http://localhost:8000
APP_NAME="Tu Aplicación"
```

### Proveedores de Correo Recomendados

#### Gmail SMTP
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-contraseña-de-aplicación
MAIL_ENCRYPTION=tls
```

#### Mailgun
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=tu-dominio.com
MAILGUN_SECRET=tu-api-key
```

#### SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=tu-sendgrid-api-key
MAIL_ENCRYPTION=tls
```

## 🚀 Endpoints del API

### 📋 Formato Estándar de Respuesta

Todas las respuestas del API siguen un formato estándar:

```json
{
  "exito": true|false,
  "codMensaje": 200|400|404|429,
  "mensajeUsuario": "Mensaje descriptivo para el usuario",
  "datoAdicional": {
    // Datos adicionales o null si no hay datos
  }
}
```

**Campos:**
- **`exito`**: `true` si la operación fue exitosa, `false` si hubo error
- **`codMensaje`**: Código interno del mensaje (200=éxito, 400=error cliente, 404=no encontrado, 429=rate limit)
- **`mensajeUsuario`**: Mensaje descriptivo para mostrar al usuario
- **`datoAdicional`**: Datos adicionales de la respuesta o `null` si no hay datos

**Nota:** Todas las respuestas devuelven código HTTP 200, diferenciándose por el campo `exito`.

### 1. Enviar Código de Recuperación

**POST** `/api/auth/send-reset-code`

```bash
curl -X POST http://localhost:8000/api/auth/send-reset-code \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "usuario@ejemplo.com"
  }'
```

**Respuesta Exitosa (200):**
```json
{
  "exito": true,
  "codMensaje": 200,
  "mensajeUsuario": "Se ha enviado un código de verificación a tu correo electrónico.",
  "datoAdicional": {
    "email": "usuario@ejemplo.com",
    "expires_in": 600
  }
}
```

**Respuesta de Error (200):**
```json
{
  "exito": false,
  "codMensaje": 404,
  "mensajeUsuario": "No se encontró un usuario con ese correo electrónico.",
  "datoAdicional": null
}
```

**Respuesta de Error - Rate Limit (200):**
```json
{
  "exito": false,
  "codMensaje": 429,
  "mensajeUsuario": "Demasiadas solicitudes. Intenta de nuevo en 60 segundos.",
  "datoAdicional": null
}
```

### 2. Verificar Código

**POST** `/api/auth/verify-reset-code`

```bash
curl -X POST http://localhost:8000/api/auth/verify-reset-code \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "usuario@ejemplo.com",
    "code": "1234"
  }'
```

**Respuesta Exitosa (200):**
```json
{
  "exito": true,
  "codMensaje": 200,
  "mensajeUsuario": "Código verificado correctamente.",
  "datoAdicional": {
    "email": "usuario@ejemplo.com",
    "verified": true
  }
}
```

**Respuesta de Error (200):**
```json
{
  "exito": false,
  "codMensaje": 400,
  "mensajeUsuario": "Código inválido o expirado.",
  "datoAdicional": null
}
```

### 3. Restablecer Contraseña

**POST** `/api/auth/reset-password`

```bash
curl -X POST http://localhost:8000/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "usuario@ejemplo.com",
    "code": "1234",
    "password": "NuevaPassword123!",
    "password_confirmation": "NuevaPassword123!"
  }'
```

**Respuesta Exitosa (200):**
```json
{
  "exito": true,
  "codMensaje": 200,
  "mensajeUsuario": "Contraseña restablecida correctamente.",
  "datoAdicional": {
    "email": "usuario@ejemplo.com",
    "updated_at": "2024-01-15T10:30:00.000Z"
  }
}
```

## 🔒 Características de Seguridad

### Envío Inmediato
- **Sin colas** - Los correos se envían inmediatamente
- **Respuesta instantánea** - El usuario recibe confirmación al instante
- **Sin dependencias** - No requiere configuración de workers o colas

### Rate Limiting
- **3 intentos** por IP por minuto para enviar códigos
- **60 segundos** de espera después de exceder el límite

### Códigos de Recuperación
- **4 dígitos** numéricos únicos
- **10 minutos** de validez
- **Uso único** - se invalidan después del uso
- **Invalidación automática** de códigos anteriores

### Validación de Contraseña
- **Mínimo 8 caracteres**
- **Letras mayúsculas y minúsculas**
- **Números**
- **Símbolos**
- **No comprometida** (verificación contra bases de datos de contraseñas filtradas)

### Limpieza Automática
- **Job programado** para limpiar códigos expirados
- **Comando manual** disponible: `php artisan password-reset:clean-expired`

## 📧 Plantilla de Correo Mejorada

El correo incluye:
- **Código destacado** con diseño moderno y gradiente
- **Emojis** para mejor experiencia visual
- **Tiempo de expiración** (10 minutos)
- **Instrucciones paso a paso** de uso
- **Consejos de seguridad** destacados
- **Diseño responsive** con Markdown
- **Envío inmediato** sin colas

### Personalización
Edita `resources/views/emails/password-reset-code.blade.php` para personalizar:
- Logo de la empresa
- Colores corporativos
- Texto del mensaje
- Información de contacto

## 🧪 Testing

### Tests Automatizados
```bash
# Ejecutar tests de recuperación de contraseña
php artisan test --filter=PasswordResetTest

# Ejecutar script de prueba de envío inmediato
php test-email-immediate.php

# Probar API con curl
./test-api-curl.sh
```

### Prueba Manual del API
```bash
# 1. Iniciar servidor
php artisan serve

# 2. Ejecutar script de prueba con curl
./test-api-curl.sh

# 3. O probar envío inmediato de correo
php test-email-immediate.php
```

## 🔧 Mantenimiento

### Limpiar Códigos Expirados
```bash
# Limpiar códigos expirados (mantener 1 día)
php artisan password-reset:clean-expired --days=1

# Programar limpieza automática (cron)
# Agregar al crontab:
# 0 2 * * * cd /path/to/project && php artisan password-reset:clean-expired
```

### Monitoreo
```bash
# Ver códigos activos
php artisan tinker
>>> App\Models\PasswordResetCode::valid()->count()

# Ver códigos expirados
>>> App\Models\PasswordResetCode::where('expires_at', '<', now())->count()
```

## 🚨 Troubleshooting

### Problemas Comunes

#### 1. Correo no se envía
```bash
# Verificar configuración
php artisan config:show mail

# Probar envío manual
php artisan tinker
>>> Mail::raw('Test', function($message) { $message->to('test@example.com')->subject('Test'); });
```

#### 2. Código no válido
```bash
# Verificar códigos en base de datos
php artisan tinker
>>> App\Models\PasswordResetCode::where('email', 'usuario@ejemplo.com')->get()
```

#### 3. Rate limiting excesivo
```bash
# Limpiar rate limiters
php artisan cache:clear
```

### Logs
```bash
# Ver logs de correo
tail -f storage/logs/laravel.log | grep -i mail

# Ver logs de rate limiting
tail -f storage/logs/laravel.log | grep -i rate
```

## 📱 Integración con Frontend

### Flujo Recomendado

1. **Pantalla de recuperación** - Usuario ingresa email
2. **Envío de código** - Llamar a `/api/auth/send-reset-code`
3. **Pantalla de verificación** - Usuario ingresa código
4. **Verificación** - Llamar a `/api/auth/verify-reset-code`
5. **Pantalla de nueva contraseña** - Usuario ingresa nueva contraseña
6. **Restablecimiento** - Llamar a `/api/auth/reset-password`
7. **Redirección** - Ir a login con mensaje de éxito

### Ejemplo en JavaScript
```javascript
// 1. Enviar código
const sendCode = async (email) => {
  const response = await fetch('/api/auth/send-reset-code', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email })
  });
  return response.json();
};

// 2. Verificar código
const verifyCode = async (email, code) => {
  const response = await fetch('/api/auth/verify-reset-code', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, code })
  });
  return response.json();
};

// 3. Restablecer contraseña
const resetPassword = async (email, code, password, passwordConfirmation) => {
  const response = await fetch('/api/auth/reset-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ 
      email, 
      code, 
      password, 
      password_confirmation: passwordConfirmation 
    })
  });
  return response.json();
};
```

## ✅ Checklist de Implementación

- [ ] Configurar variables de correo en `.env`
- [ ] Probar envío de correo con `php artisan tinker`
- [ ] Ejecutar tests: `php artisan test --filter=PasswordResetTest`
- [ ] Probar flujo completo con script de prueba
- [ ] Configurar limpieza automática de códigos expirados
- [ ] Personalizar plantilla de correo
- [ ] Integrar con frontend
- [ ] Documentar para usuarios finales 