# RSISTANC

**Boutique de Wellness 360°** - Aplicación móvil iOS/Android para la gestión integral de clases de bienestar y e-commerce.

## 📱 Descripción

RSISTANC es una aplicación móvil que actúa como "boutique de wellness 360°". Su propósito principal es vender paquetes de clases (12, 24, 40) para disciplinas presenciales y en vivo –Cycling, Solid Reformer, Pilates, etc.– y permitir al usuario reservar su puesto, añadir acompañantes y comprar extras (batidos, accesorios) de forma integrada.

## 🚀 Características Principales

### 🔐 On-boarding y Autenticación
- **Registro en tres pasos**: credenciales, datos personales, contacto
- **SSO**: Integración con Google/Facebook
- **Seguridad**: Estado de cuenta gestionado por JWT + refresh
- **Protección**: Bloqueo por intentos fallidos

### 💎 Planes y Membresías
- **Catálogo de paquetes**: Diferentes opciones de clases (12, 24, 40)
- **Pago integrado**: Tarjeta tokenizada en la app
- **Contador de clases**: Seguimiento automático del consumo
- **Upgrade automático**: Tiers Gold y Black según acumulado

### 📅 Reserva de Clases
- **Filtros avanzados**: Por modalidad, disciplina, profesor y día
- **Mapa de asientos**: Selección en tiempo real
- **Acompañantes**: Hasta 3 personas que consumen cupos del mismo plan
- **Gestión flexible**: Reprogramación/cancelación dentro de la política
- **Reintegro automático**: Devolución de clases según políticas

### 🛍️ E-commerce Complementario
- **Tienda integrada**: Indumentaria y productos wellness
- **Carrito de compras**: Experiencia completa de e-commerce
- **Sistema de cupones**: Descuentos y promociones
- **Seguimiento de pedidos**: Estado en tiempo real

### 👤 Perfil y Progreso
- **Historial completo**: Registro de clases tomadas
- **Evaluaciones**: Sistema de rating para profesores
- **KPIs de avance**: Barras de progreso y métricas
- **Sistema de puntos**: Gamificación del progreso
- **Gestión de cuenta**: Métodos de pago, direcciones, notificaciones

### 📊 Back-office y Auditoría
- **Registro transaccional**: Todas las operaciones registradas
- **Reportes operativos**: Métricas de negocio
- **Auditoría de seguridad**: Logs de accesos y actividad
- **Dashboard administrativo**: Gestión integral del sistema

## 🎯 Propuesta de Valor

La aplicación ofrece una **experiencia end-to-end completa**:

1. **Descubre** y compra un plan de clases
2. **Reserva** tu clase con extras incluidos
3. **Acumula** puntos por tu actividad
4. **Adquiere** productos complementarios
5. **Todo** bajo la misma identidad digital

## 🛠️ Tecnologías

### Backend (Laravel)
- **PHP 8.3+**
- **Laravel 12.0+**
- **Laravel API Resources** para APIs REST estructuradas
- **Arquitectura limpia** con principios SOLID
- **Patrones Repository/Service**
- **PSR-12** coding standards
- **JWT Authentication** con refresh tokens

### Base de Datos
- **MySQL/PostgreSQL**
- **Migraciones** y seeders
- **Eloquent ORM**
- **Indexación optimizada**

### APIs y Integraciones
- **Laravel API Resources** para respuestas estructuradas y consistentes
- **API Controllers** con filtros avanzados y paginación
- **Laravel Sanctum** para autenticación de APIs
- **Scramble** para documentación automática de APIs
- **Relaciones optimizadas** con eager loading controlado
- **Versionado de APIs**
- **Integración con pasarelas de pago**
- **SSO con Google/Facebook**

### Infraestructura
- **Redis** para caching y sesiones
- **Queue system** para tareas en background
- **Laravel Horizon** para monitoreo de colas
- **Logging** y monitoreo avanzado

## 📁 Estructura del Proyecto

```
rsistanc/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Controladores finales y read-only
│   │   ├── Requests/        # Form Requests para validación
│   │   └── Resources/       # API Resources
│   ├── Models/              # Modelos finales Eloquent
│   ├── Services/            # Servicios de negocio finales
│   ├── Repositories/        # Patrones Repository
│   ├── Enums/               # Enums PHP 8.3+
│   └── Exceptions/          # Excepciones personalizadas
├── database/
│   ├── migrations/          # Migraciones de BD
│   ├── factories/           # Factories para datos de prueba
│   └── seeders/            # Seeders con datos realistas
├── routes/                  # Rutas organizadas por módulo
└── tests/                  # Tests unitarios y de integración
```

## 🗄️ Modelos y Base de Datos

### Modelos Implementados

#### 👤 **Sistema de Usuarios**
- **`User`** - Usuario principal del sistema
- **`UserProfile`** - Perfil detallado (nombre, edad, género, talla)
- **`UserContact`** - Contactos múltiples (teléfono, dirección)
- **`SocialAccount`** - Cuentas sociales vinculadas (Google/Facebook)
- **`LoginAudit`** - Auditoría de intentos de login

#### 🔧 **Enums PHP 8.3+**
- **`Gender`** - Géneros (female, male, other, na)
- **`AuthProvider`** - Proveedores OAuth (google, facebook)

### Relaciones de Base de Datos

```
User (1) ──── (1) UserProfile
User (1) ──── (N) UserContact
User (1) ──── (N) SocialAccount
User (1) ──── (N) LoginAudit
```

### Características de los Modelos

✅ **Strict typing** con `declare(strict_types=1)`
✅ **Clases finales** para prevenir herencia
✅ **Enums PHP 8.3+** con métodos helper
✅ **Encriptación automática** de tokens sensibles
✅ **Scopes útiles** para consultas comunes
✅ **Accessors/Mutators** para datos calculados
✅ **Relaciones tipadas** con return types
✅ **Índices optimizados** para rendimiento



## 🚦 Instalación y Configuración

### 📋 **Requisitos del Sistema**
- PHP 8.2+
- Composer
- MySQL 8.0+ o PostgreSQL 13+
- Node.js 18+ (opcional, para frontend)

### 🔧 **Instalación Paso a Paso**

```bash
# 1. Clonar el repositorio
git clone [repository-url]
cd rsistanc

# 2. Instalar dependencias de PHP
composer install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar base de datos en .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=rsistanc
# DB_USERNAME=tu_usuario
# DB_PASSWORD=tu_contraseña
```

### 🗄️ **Configuración de Base de Datos**

```bash
# 5. Ejecutar migraciones (crea todas las tablas)
php artisan migrate

# 6. Ejecutar seeders (datos de prueba)
php artisan db:seed

# O ejecutar ambos en un comando
php artisan migrate --seed
```

### 🔐 **Configuración de Laravel Sanctum (API Authentication)**

```bash
# 7. Instalar Laravel Sanctum
composer require laravel/sanctum

# 8. Publicar configuración de Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 9. Ejecutar migraciones de Sanctum (tabla personal_access_tokens)
php artisan migrate

# 10. Limpiar caché de configuración
php artisan config:clear
```

### 📚 **Configuración de Documentación API (Scramble)**

```bash
# 11. Instalar Scramble para documentación automática
composer require dedoc/scramble

# 12. Publicar configuración de Scramble
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider"

# 13. Limpiar caché para reconocer Scramble
php artisan config:clear
```

### 🚀 **Iniciar el Servidor**

```bash
# 14. Iniciar servidor de desarrollo
php artisan serve

# La aplicación estará disponible en:
# http://localhost:8000

# Documentación API disponible en:
# http://localhost:8000/docs/api
```


## 📚 Documentación de API

### 🌐 **Documentación Interactiva con Scramble**

La API incluye **documentación automática** generada con Scramble:

- **📖 Documentación Interactiva:** [http://rsistanc.test/docs/api](http://rsistanc.test/docs/api)
- **📄 Especificación OpenAPI:** [http://rsistanc.test/docs/api.json](http://rsistanc.test/docs/api.json)

### ✨ **Características de la Documentación**

- **🎯 Try It** - Prueba endpoints directamente desde la documentación
- **📊 Ejemplos reales** de requests y responses
- **🔍 Filtros y parámetros** completamente documentados
- **📱 Interfaz responsive** con Stoplight Elements
- **🏷️ Agrupación por categorías** (Users, Contacts, System)
- **🔄 Actualización automática** desde anotaciones PHPDoc

### 📋 **Endpoints Disponibles**

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
- ✅ **Perfecto para:** Apps móviles, frontend público, auto-registro

#### **`POST /api/users`** - Para administradores
- ✅ **Creación administrativa** de usuarios
- ✅ **Sin login automático** (no genera token)
- ✅ **Más control** sobre el proceso de creación
- ✅ **Perfecto para:** Panel de administración, gestión de usuarios

### 🔧 **Características Técnicas**

- **Generación automática** desde anotaciones PHPDoc
- **Validaciones** y esquemas de datos documentados
- **Códigos de error** con ejemplos de respuesta
- **Filtros avanzados** para búsqueda y paginación
- **Seguridad** con ocultación de datos sensibles

Para más detalles, consulta el archivo [DOCUMENTACION_API.md](DOCUMENTACION_API.md).

## 🚀 **Ejemplos de Uso de la API**

### 🔐 **Autenticación**

```bash
# Registrar nuevo usuario
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Ana Torres",
    "email": "ana@ejemplo.com",
    "password": "MiContraseña123!",
    "password_confirmation": "MiContraseña123!"
  }'

# Iniciar sesión
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "ana@ejemplo.com",
    "password": "MiContraseña123!"
  }'

# Usar token en requests protegidos
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer 1|abc123def456..." \
  -H "Accept: application/json"
```

### 👤 **Gestión de Usuarios**

```bash
# Listar usuarios con filtros
curl -X GET "http://localhost:8000/api/users?search=Ana&has_profile=true&per_page=10" \
  -H "Accept: application/json"

# Obtener usuario específico
curl -X GET http://localhost:8000/api/users/1 \
  -H "Accept: application/json"

# Crear contacto para usuario
curl -X POST http://localhost:8000/api/users/1/contacts \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "+51 987 654 321",
    "address_line": "Av. Javier Prado Este 4200",
    "city": "Lima",
    "country": "PE",
    "is_primary": true
  }'
```

### 📊 **Respuestas de Ejemplo**

```json
// POST /api/auth/login - Respuesta exitosa
{
  "user": {
    "id": 1,
    "name": "Ana Torres",
    "email": "ana@ejemplo.com",
    "full_name": "Ana Lucía Torres",
    "has_complete_profile": true
  },
  "token": {
    "access_token": "1|abc123def456...",
    "token_type": "Bearer"
  },
  "meta": {
    "login_count": 15,
    "last_login": "2024-01-15T10:30:00.000Z"
  }
}
```

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Tests con cobertura
php artisan test --coverage

# Tests específicos
php artisan test --filter UserTest
```

## 🔧 **Troubleshooting**

### ❌ **Problemas Comunes**

#### **Error: "Field 'ip' doesn't have a default value"**
```bash
# Asegúrate de que las migraciones estén actualizadas
php artisan migrate:fresh --seed
```

#### **Error: "Unauthenticated" en endpoints protegidos**
```bash
# Verifica que el token esté en el header correcto
Authorization: Bearer 1|tu_token_aqui
```

#### **Error: "Class 'Laravel\Sanctum\HasApiTokens' not found"**
```bash
# Instala Sanctum si no está instalado
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

#### **Documentación API no se muestra**
```bash
# Limpia caché y regenera documentación
php artisan config:clear
php artisan cache:clear
# Visita: http://localhost:8000/docs/api
```

### 🗄️ **Comandos Útiles**

```bash
# Limpiar todas las cachés
php artisan optimize:clear

# Regenerar autoload de Composer
composer dump-autoload

# Ver todas las rutas API
php artisan route:list --path=api

# Verificar configuración de Sanctum
php artisan config:show auth.guards.sanctum

# Crear usuario de prueba manualmente
php artisan tinker
>>> User::factory()->create(['email' => 'test@test.com'])
```

### 📊 **Estructura de Base de Datos**

```sql
-- Tablas principales creadas por las migraciones:
users                    -- Usuarios del sistema
user_profiles           -- Perfiles de usuario
user_contacts           -- Contactos de usuario
social_accounts         -- Cuentas sociales OAuth
login_audits           -- Auditoría de inicios de sesión
personal_access_tokens -- Tokens de Sanctum
```

---

## 🚀 **Comandos de Instalación Resumidos:**

```bash
# Instalación completa en un solo bloque
git clone [repository-url] && cd rsistanc
composer install
cp .env.example .env && php artisan key:generate
# Configurar .env con BD
php artisan migrate --seed
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
composer require dedoc/scramble
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider"
php artisan config:clear
php artisan serve
```

---

**🎉 ¡Tu API RSISTANC con autenticación Sanctum está lista para usar!**

**📖 Documentación completa:** [http://localhost:8000/docs/api](http://localhost:8000/docs/api)

