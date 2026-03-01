# The Palatin — Hoja de ruta del proyecto

Arquitectura: **Frontend estático (HTML/CSS/JS) + API REST en PHP + MySQL**  
El JS es la única capa de presentación y se comunica con el backend exclusivamente mediante fetch a endpoints REST, lo que permite migrar el backend (ej. a NestJS) sin tocar el frontend.

---

## Paso 1 — Modelado de base de datos (MySQL)

> **Empieza aquí.** Todo el proyecto depende de esta estructura.

Tablas mínimas:

```sql
users           -- administradores del sistema
places          -- los lugares/alojamientos
place_images    -- imágenes de cada lugar
amenities       -- wifi, piscina, cocina, etc.
place_amenities -- relación lugar ↔ amenidad (tabla pivote)
reservations    -- reservas de usuarios finales
```

Entregable: archivo `db/schema.sql` con todas las tablas, relaciones y claves foráneas.

---

## Paso 2 — API REST en PHP

> Con el modelo listo, se construyen los endpoints. PHP solo devuelve y recibe JSON.

Endpoints prioritarios:

```
POST   /api/auth/login          -- autenticación, devuelve JWT
GET    /api/places              -- lista de lugares (público)
GET    /api/places/:id          -- detalle de un lugar (público)
POST   /api/places              -- crear lugar (protegido)
PUT    /api/places/:id          -- editar lugar (protegido)
DELETE /api/places/:id          -- eliminar lugar (protegido)
POST   /api/reservations        -- crear reserva (público)
```

Consideraciones:
- Usar **JWT** para proteger los endpoints del admin.
- Separar rutas públicas de rutas protegidas.
- Centralizar la conexión a BD en `api/config/database.php`.

Entregable: carpeta `api/` con todos los endpoints funcionando y probados (ej. con Postman).

---

## Paso 3 — Panel de administración

> Con la API lista, el admin es un frontend independiente que la consume.

Flujo de autenticación:
```
JS admin → POST /api/auth/login → PHP valida credenciales → devuelve JWT
JS admin → GET /api/places (JWT en header Authorization) → PHP verifica → devuelve datos
```

Vistas necesarias:
- Login
- Dashboard / listado de lugares
- Formulario crear/editar lugar
- Gestión de imágenes y amenidades

Entregable: carpeta `admin/` con HTML/CSS/JS, sin dependencia de ningún framework de backend.

---

## Paso 4 — Conectar el frontend público

> El sitio que ya existe consume los mismos endpoints públicos de la API.

Archivos JS a crear:
- `public/js/config.js` — URL base de la API (único archivo a cambiar al migrar backend)
- `public/js/places.js` — carga y pinta los lugares en `rooms.html`
- `public/js/place-detail.js` — detalle en `room-details.html`
- `public/js/reservation.js` — formulario de reserva

Ejemplo de config.js:
```js
const API_BASE = 'http://localhost/palatin/api';
```

---

## Estructura de carpetas

```
palatin/
├── public/              ← sitio frontend actual (HTML/CSS/JS)
│   └── js/
│       ├── config.js
│       ├── places.js
│       ├── place-detail.js
│       └── reservation.js
├── admin/               ← panel de administración
│   ├── index.html
│   └── js/
├── api/                 ← backend PHP
│   ├── auth/
│   ├── places/
│   ├── reservations/
│   └── config/
│       └── database.php
└── db/
    └── schema.sql       ← modelo de base de datos
```

---

## Resumen

| Paso | Qué | Por qué primero |
|------|-----|-----------------|
| 1 | Modelado MySQL | Todo depende de la estructura de datos |
| 2 | API REST PHP | Sin API no hay nada que consumir |
| 3 | Panel admin | Gestiona los datos vía la API |
| 4 | Frontend público | Muestra los datos al usuario final |
