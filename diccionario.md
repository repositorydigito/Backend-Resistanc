1. user_profiles – Datos personales y de talla
Campo	Tipo SQL	NULO	PK/FK/Índice	Descripción
id	BIGINT UNSIGNED AUTO_INCREMENT	NO	PK	Identificador del perfil
user_id	BIGINT UNSIGNED	NO	FK + UNIQUE	Relación 1:1 con users
first_name	VARCHAR(60)	NO	—	Nombre(s) del usuario
last_name	VARCHAR(60)	NO	—	Apellido(s)
birth_date	DATE	NO	—	Fecha de nacimiento (YYYY-MM-DD)
gender	ENUM('female','male','other','na')	NO	—	Género declarado
shoe_size_eu	TINYINT UNSIGNED	SÍ	—	Talla de calzado (sistema EU)
created_at	TIMESTAMP	NO	—	Generado por Laravel
updated_at	TIMESTAMP	NO	—	Generado por Laravel

2. user_contacts – Teléfonos y direcciones (0…N por usuario)
Campo	Tipo SQL	NULO	PK/FK/Índice	Descripción
id	BIGINT UNSIGNED AUTO_INCREMENT	NO	PK	Identificador de contacto
user_id	BIGINT UNSIGNED	NO	FK	Propietario del contacto
phone	VARCHAR(15)	NO	UNIQUE	Número E.164 (+51XXXXXXXXX)
address_line	VARCHAR(255)	NO	—	Dirección completa o línea 1
city	VARCHAR(80)	SÍ	—	Ciudad o distrito
country	CHAR(2) DEFAULT 'PE'	NO	—	ISO-3166-1 alpha-2
is_primary	BOOLEAN DEFAULT TRUE	NO	INDEX(user_id,is_primary)	Flag “principal”
created_at	TIMESTAMP	NO	—	—
updated_at	TIMESTAMP	NO	—	—

3. social_accounts – Vinculación con proveedores SSO
Campo	Tipo SQL	NULO	PK/FK/Índice	Descripción
id	BIGINT UNSIGNED AUTO_INCREMENT	NO	PK	Identificador de vínculo
user_id	BIGINT UNSIGNED	NO	FK	Usuario al que pertenece
provider	ENUM('google','facebook')	NO	INDEX	Nombre del proveedor
provider_uid	VARCHAR(191)	NO	UNIQUE(provider,provider_uid)	ID devuelto por el SSO
provider_email	VARCHAR(191)	SÍ	—	E-mail verificado por el SSO
token	TEXT	SÍ	—	Token OAuth encriptado
token_expires_at	TIMESTAMP	SÍ	—	Caducidad del token
created_at	TIMESTAMP	NO	—	—
updated_at	TIMESTAMP	NO	—	—

4. login_audits (opcional) – Trazas de acceso y seguridad
Campo	Tipo SQL	NULO	PK/FK/Índice	Descripción
id	BIGINT UNSIGNED AUTO_INCREMENT	NO	PK	Identificador de evento
user_id	BIGINT UNSIGNED	SÍ	FK	Puede ser NULL si el intento no asoció usuario
ip	VARCHAR(45)	NO	INDEX(user_id)	IPv4/IPv6 del cliente
user_agent	VARCHAR(255)	NO	—	Cadena del navegador/app
success	BOOLEAN	NO	—	1 = login correcto, 0 = fallido
created_at	TIMESTAMP	NO	—	Marca temporal