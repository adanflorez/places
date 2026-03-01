# The Palatin — Guía de despliegue en producción

## Requisitos del hosting

- PHP >= 8.0
- MySQL >= 5.7
- Apache con `mod_rewrite` habilitado (para URLs limpias)
- Acceso SSH o al administrador de archivos de cPanel

---

## 1. Base de datos

Desde cPanel → **MySQL Databases**:

1. Crear la base de datos (ej. `usuario_places`)
2. Crear el usuario de BD (ej. `usuario_places_user`) con una contraseña segura
3. Asignar el usuario a la base de datos con **todos los privilegios**

Luego importar el schema desde cPanel → **phpMyAdmin** → pestaña **Importar**:

```
db/schema.sql
```

---

## 2. Subir los archivos

Sube el proyecto completo a `public_html/` via FTP o el administrador de archivos de cPanel.

> ⚠️ **No subas el archivo `.env`** — lo crearás directamente en el servidor en el siguiente paso.

Estructura esperada en el servidor:

```
public_html/
├── admin/
├── api/
├── db/
├── img/
├── js/
├── css/
├── .env          ← se crea en el servidor, nunca desde el repo
├── index.html
└── ...
```

---

## 3. Crear el `.env` en producción

Crea el archivo `.env` directamente en el servidor (cPanel → Editor de archivos o SSH):

```env
DB_HOST=localhost
DB_NAME=usuario_places
DB_USER=usuario_places_user
DB_PASS=tu_password_segura_aqui
JWT_SECRET=genera_una_clave_larga_y_aleatoria_aqui
```

### Generar un JWT_SECRET seguro

Desde SSH ejecuta:
```bash
php -r "echo bin2hex(random_bytes(32));"
```
Copia el resultado y úsalo como `JWT_SECRET`. **Nunca uses el valor de desarrollo en producción.**

---

## 4. Permisos de archivos

```bash
# Archivos: lectura para el servidor web
find public_html/ -type f -exec chmod 644 {} \;

# Carpetas: acceso de navegación
find public_html/ -type d -exec chmod 755 {} \;

# .env: solo lectura para el propietario
chmod 600 public_html/.env

# Carpeta de uploads (cuando se implemente)
chmod 755 public_html/uploads/
```

---

## 5. Verificar mod_rewrite (URLs limpias)

El archivo `api/.htaccess` requiere que Apache tenga `mod_rewrite` habilitado. En cPanel esto generalmente ya está activo.

Si las rutas limpias no funcionan, añade esto al `.htaccess` raíz de `public_html/`:

```apache
Options -Indexes
RewriteEngine On
```

---

## 6. Variables que cambian entre local y producción

| Variable | Local | Producción |
|---|---|---|
| `DB_HOST` | `localhost` | `localhost` (casi siempre igual en cPanel) |
| `DB_NAME` | `places` | `usuario_places` (prefijo del hosting) |
| `DB_USER` | `places_user` | `usuario_places_user` (prefijo del hosting) |
| `DB_PASS` | `places_user123` | contraseña segura |
| `JWT_SECRET` | cualquier valor | cadena aleatoria de 64+ caracteres |

---

## 7. Cambiar la URL base de la API

Si la API vive en un subdominio o ruta diferente, actualiza `API_BASE` en:

- `admin/js/config.js` → para el panel de administración
- `public/js/config.js` → para el sitio público (cuando se implemente)

```js
// Ejemplos
const API_BASE = 'https://tudominio.com/api';        // misma carpeta
const API_BASE = 'https://api.tudominio.com';         // subdominio
```

---

## 8. Crear el primer usuario administrador en producción

Desde SSH:

```bash
cd public_html
php -r "
require_once 'api/config/database.php';
\$db = getDB();
\$stmt = \$db->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
\$stmt->execute(['Admin', 'tu@email.com', password_hash('tu_password_segura', PASSWORD_BCRYPT), 'admin']);
echo 'Usuario creado correctamente' . PHP_EOL;
"
```

> Si no tienes acceso SSH, puedes ejecutar este script como un archivo PHP temporal, accederlo una vez desde el navegador y luego **eliminarlo inmediatamente**.

---

## 9. Checklist antes de salir a producción

- [ ] `.env` creado en el servidor con valores reales
- [ ] `.env` **no** está en el repositorio ni en el FTP público
- [ ] `JWT_SECRET` es una cadena aleatoria larga
- [ ] Contraseña de BD es segura
- [ ] Contraseña del usuario admin cambiada desde el panel
- [ ] Permisos de archivos correctos (`644` archivos, `755` carpetas, `600` `.env`)
- [ ] `mod_rewrite` habilitado en Apache
- [ ] Schema importado correctamente (verificar tablas en phpMyAdmin)
- [ ] Probar login desde el admin en producción
