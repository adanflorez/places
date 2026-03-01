# The Palatin

Plataforma mediadora entre viajeros y alojamientos. Conecta a huéspedes con anfitriones verificados en los destinos más increíbles.

## Stack

| Capa | Tecnología |
|---|---|
| Frontend público | HTML / CSS / JS vanilla |
| Panel de administración | HTML / CSS / JS vanilla |
| Backend | PHP 8.x (API REST) |
| Base de datos | MySQL |
| Autenticación | JWT (implementación nativa, sin librerías externas) |

---

## Requisitos

- PHP >= 8.0
- MySQL >= 5.7
- Apache (opcional, para URLs limpias) o el servidor built-in de PHP

---

## Instalación local

### 1. Clonar el repositorio

```bash
git clone <url-del-repo>
cd palatin-gh-pages
```

### 2. Instalar PHP y Apache (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install -y php libapache2-mod-php php-mysql php-mbstring php-json apache2
```

### 3. Crear la base de datos y el usuario en MySQL

```sql
CREATE DATABASE places CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'places_user'@'localhost' IDENTIFIED BY 'places_user123';
GRANT ALL PRIVILEGES ON places.* TO 'places_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Importar el schema

```bash
mysql -u places_user -pplaces_user123 places < db/schema.sql
```

Esto crea todas las tablas e inserta las amenidades base (WiFi, Cocina, Piscina, etc.).

### 5. Configurar variables de entorno

Copia el archivo de ejemplo y ajusta los valores:

```bash
cp .env.example .env
```

Contenido del `.env`:

```env
DB_HOST=localhost
DB_NAME=places
DB_USER=places_user
DB_PASS=places_user123
JWT_SECRET=cambia_este_valor_en_produccion
```

> ⚠️ El `.env` nunca debe subirse al repositorio. Está incluido en `.gitignore`.

### 6. Crear el usuario administrador

```bash
php -r "
require_once 'api/config/database.php';
\$db = getDB();
\$stmt = \$db->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
\$stmt->execute(['Admin', 'admin@palatin.com', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
echo 'Usuario creado correctamente' . PHP_EOL;
"
```

### 7. Levantar el servidor

```bash
php -S localhost:8000
```

---

## Acceso

| Recurso | URL |
|---|---|
| Sitio público | http://localhost:8000 |
| Panel de administración | http://localhost:8000/admin/ |

**Credenciales por defecto del admin:**

| Campo | Valor |
|---|---|
| Email | admin@palatin.com |
| Contraseña | admin123 |

> Cambia la contraseña después del primer acceso.

---

## Estructura del proyecto

```
palatin/
├── public/                  ← Sitio frontend (HTML/CSS/JS)
│   ├── index.html
│   ├── rooms.html
│   ├── room-details.html
│   ├── about-us.html
│   ├── contact.html
│   ├── css/
│   ├── js/
│   └── img/
├── admin/                   ← Panel de administración
│   ├── index.html           ← Login
│   ├── dashboard.html       ← Estadísticas
│   ├── places.html          ← Listado de lugares
│   ├── place-form.html      ← Crear / editar lugar
│   ├── reservations.html    ← Gestión de reservas
│   ├── sidebar.html         ← Sidebar reutilizable
│   ├── css/admin.css
│   └── js/config.js         ← API_BASE + Auth + api()
├── api/                     ← Backend PHP
│   ├── .htaccess            ← Rutas limpias (requiere Apache)
│   ├── auth/
│   │   └── login.php        ← POST /api/auth/login
│   ├── places/
│   │   ├── index.php        ← GET|POST|PUT|DELETE /api/places
│   │   └── amenities.php    ← GET /api/places/amenities
│   ├── reservations/
│   │   └── index.php        ← GET|POST|PUT /api/reservations
│   └── config/
│       ├── config.php       ← Constantes desde .env
│       ├── database.php     ← Conexión PDO
│       ├── jwt.php          ← JWT sin dependencias externas
│       └── helpers.php      ← response() y authRequired()
├── db/
│   └── schema.sql           ← Tablas y datos base
├── .env                     ← Variables de entorno (no subir al repo)
├── .env.example             ← Plantilla de variables de entorno
├── ROADMAP.md               ← Hoja de ruta del proyecto
└── README.md
```

---

## API REST

### Autenticación

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| POST | `/api/auth/login.php` | No | Obtener JWT |

**Body:**
```json
{
  "email": "admin@palatin.com",
  "password": "admin123"
}
```

**Respuesta:**
```json
{
  "token": "eyJ...",
  "user": { "id": 1, "name": "Admin", "role": "admin" }
}
```

---

### Lugares

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| GET | `/api/places/index.php` | No | Listar lugares activos |
| GET | `/api/places/index.php?id=1` | No | Detalle con imágenes y amenidades |
| POST | `/api/places/index.php` | ✅ JWT | Crear lugar |
| PUT | `/api/places/index.php?id=1` | ✅ JWT | Actualizar lugar |
| DELETE | `/api/places/index.php?id=1` | ✅ JWT | Desactivar lugar (soft delete) |
| GET | `/api/places/amenities.php` | No | Listar amenidades disponibles |

---

### Reservas

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| POST | `/api/reservations/index.php` | No | Crear reserva (valida disponibilidad) |
| GET | `/api/reservations/index.php` | ✅ JWT | Listar reservas |
| GET | `/api/reservations/index.php?id=1` | ✅ JWT | Detalle de reserva |
| PUT | `/api/reservations/index.php?id=1` | ✅ JWT | Cambiar estado |

**Filtros disponibles en GET /reservations:**
```
?status=pending|confirmed|cancelled
?place_id=1
?month=2026-03
```

**Cambiar estado:**
```json
{ "status": "confirmed" }
```

---

### Enviar JWT en las peticiones protegidas

```
Authorization: Bearer eyJ...
```

---

## Base de datos

| Tabla | Descripción |
|---|---|
| `users` | Administradores del panel |
| `places` | Lugares / alojamientos |
| `place_images` | Imágenes de cada lugar |
| `amenities` | Catálogo de amenidades |
| `place_amenities` | Relación lugar ↔ amenidad |
| `reservations` | Reservas de huéspedes |

---

## Migrar el backend

El frontend y el admin consumen la API únicamente a través de la variable `API_BASE` en `admin/js/config.js` (admin) y `public/js/config.js` (sitio público). Para migrar a otro backend (NestJS, Laravel, etc.) solo se cambia esa variable:

```js
// admin/js/config.js
const API_BASE = 'https://api.tudominio.com'; // antes apuntaba a PHP
```

El resto del código no requiere cambios.
