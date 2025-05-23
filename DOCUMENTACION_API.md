# 📚 Documentación de API RSISTANC con Scramble

## 🚀 Acceso a la Documentación

La documentación interactiva de la API está disponible en:

**🌐 URL Principal:** [http://rsistanc.test/docs/api](http://rsistanc.test/docs/api)

**📄 Especificación OpenAPI:** [http://rsistanc.test/docs/api.json](http://rsistanc.test/docs/api.json)

## ✨ Características de la Documentación

### 🎯 **Funcionalidades Principales**
- ✅ **Documentación automática** generada desde el código
- ✅ **Interfaz interactiva** con Stoplight Elements
- ✅ **Try It** - Prueba endpoints directamente desde la documentación
- ✅ **Ejemplos de respuesta** reales y detallados en español
- ✅ **Validaciones** y esquemas de datos
- ✅ **Filtros y parámetros** de consulta documentados
- ✅ **Códigos de error** y respuestas de ejemplo

### 📊 **Endpoints Documentados**

#### **🧪 Sistema**
- `GET /api/test` - Verificar estado de la API

#### **🔐 Autenticación (Laravel Sanctum)**
- `POST /api/auth/register` - 🆕 **Auto-registro público** (genera token automáticamente)
- `POST /api/auth/login` - Iniciar sesión (genera token de acceso)
- `GET /api/auth/me` - Obtener usuario autenticado 🔒
- `POST /api/auth/logout` - Cerrar sesión actual 🔒
- `POST /api/auth/logout-all` - Cerrar todas las sesiones 🔒
- `POST /api/auth/refresh` - Renovar token de acceso 🔒

#### **👤 Usuarios (Gestión Administrativa)**
- `GET /api/users` - Listar usuarios (con filtros y paginación)
- `POST /api/users` - **Crear usuario (para administradores)** - sin login automático
- `GET /api/users/{id}` - Obtener usuario específico
- `PUT /api/users/{id}` - Actualizar usuario
- `DELETE /api/users/{id}` - Eliminar usuario
- `GET /api/users/{id}/profile` - Obtener perfil de usuario
- `GET /api/users/{id}/contacts` - Obtener contactos de usuario
- `GET /api/users/{id}/social-accounts` - Obtener cuentas sociales
- `GET /api/users/{id}/login-audits` - Obtener auditoría de inicios de sesión

#### **📞 Contactos de Usuario**
- `GET /api/users/{id}/contacts` - Listar contactos de usuario
- `POST /api/users/{id}/contacts` - Crear contacto para usuario
- `GET /api/users/{id}/contacts/{contact_id}` - Obtener contacto específico
- `PUT /api/users/{id}/contacts/{contact_id}` - Actualizar contacto
- `DELETE /api/users/{id}/contacts/{contact_id}` - Eliminar contacto

### 🔍 **Diferencias entre Endpoints de Registro**

#### **`POST /api/auth/register`** - Para usuarios finales
- ✅ **Auto-registro público** (cualquiera puede registrarse)
- ✅ **Login automático** después del registro
- ✅ **Genera token** de acceso inmediatamente
- ✅ **Crea auditoría** de login automáticamente
- ✅ **Perfecto para:** Apps móviles, frontend público, auto-registro

#### **`POST /api/users`** - Para administradores
- ✅ **Creación administrativa** de usuarios
- ✅ **Sin login automático** (no genera token)
- ✅ **Más control** sobre el proceso de creación
- ✅ **Puede requerir** autenticación de administrador
- ✅ **Perfecto para:** Panel de administración, gestión de usuarios

## 🛠️ Configuración de Scramble

### **Archivo de Configuración**
La configuración se encuentra en `config/scramble.php`:

```php
// Información de la API
'info' => [
    'version' => '1.0.0',
    'description' => '🚀 API RSISTANC - Sistema completo...'
],

// Configuración de UI
'ui' => [
    'title' => 'Documentación API RSISTANC',
    'theme' => 'light',
    'layout' => 'responsive'
],

// Servidores
'servers' => [
    'Desarrollo Local' => 'http://rsistanc.test/api',
    'Producción' => env('APP_URL') . '/api'
]
```

### **Anotaciones en Controladores**

Los controladores usan anotaciones PHPDoc para generar la documentación:

```php
/**
 * @tags Usuarios
 */
class UserController extends Controller
{
    /**
     * Lista todos los usuarios del sistema
     *
     * @summary Listar usuarios
     * @operationId getUsersList
     *
     * @queryParam search string Buscar por nombre o correo electrónico
     * @queryParam has_profile boolean Filtrar por perfil
     * @queryParam per_page integer Usuarios por página
     *
     * @response 200 { "data": [...], "meta": {...} }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // ...
    }
}
```

## 🔧 Mantenimiento

### **Regenerar Documentación**
La documentación se actualiza automáticamente, pero puedes forzar la regeneración:

```bash
# Limpiar caché
php artisan cache:clear

# Refrescar la página de documentación
# http://rsistanc.test/docs/api
```

### **Añadir Nuevos Endpoints**
1. Crear el controlador con anotaciones PHPDoc en español
2. Registrar las rutas en `routes/api.php`
3. La documentación se actualiza automáticamente

### **Personalizar Respuestas**
Usa las anotaciones `@response` para documentar respuestas:

```php
/**
 * @response 200 {
 *   "id": 1,
 *   "name": "María González",
 *   "email": "maria.gonzalez@ejemplo.com"
 * }
 *
 * @response 422 {
 *   "message": "Los datos proporcionados no son válidos.",
 *   "errors": { "email": ["Este correo ya existe"] }
 * }
 */
```

## 📝 Mejores Prácticas

### **Anotaciones Recomendadas**
- `@tags` - Agrupa endpoints por categoría (en español)
- `@summary` - Descripción corta del endpoint
- `@operationId` - ID único para el endpoint
- `@queryParam` - Parámetros de consulta
- `@bodyParam` - Parámetros del cuerpo de la petición
- `@response` - Ejemplos de respuestas

### **Estructura de Documentación**
1. **Descripción clara** del propósito del endpoint en español
2. **Parámetros** bien documentados con ejemplos realistas
3. **Respuestas de ejemplo** para casos exitosos y de error
4. **Códigos de estado HTTP** apropiados
5. **Ejemplos con datos en español** (nombres, direcciones, etc.)

## 🎨 Personalización de UI

La interfaz se puede personalizar en `config/scramble.php`:

- **Tema:** `light` o `dark`
- **Layout:** `sidebar`, `responsive`, o `stacked`
- **Logo:** URL de imagen personalizada
- **Título:** "Documentación API RSISTANC"

## 🌟 Características Implementadas

### **✅ Traducción Completa al Español**
- Títulos y descripciones en español
- Ejemplos con nombres y direcciones peruanas
- Mensajes de error traducidos
- Categorías de endpoints en español

### **✅ Ejemplos Realistas**
- Nombres: María González, Carlos Mendoza, Ana Lucía Torres
- Direcciones: Av. Javier Prado, Jr. de la Unión, Av. Benavides
- Teléfonos: +51 987 654 321
- Códigos de país: PE (Perú)

### **✅ Documentación Completa**
- Todos los endpoints documentados
- Parámetros de consulta explicados
- Códigos de respuesta con ejemplos
- Validaciones y errores documentados

---

**¡Tu API RSISTANC ahora tiene documentación profesional completamente en español! 🎉**
