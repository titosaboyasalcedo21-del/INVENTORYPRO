# InventoryPro

Aplicación web de inventario y ventas con Laravel 13, Blade, Bootstrap 5,
MySQL/MariaDB y Chart.js. Incluye autenticación, catálogo, compras, ventas,
control de existencias, movimientos y reportes.

## Despliegue con Docker Compose

Se requiere Docker Engine y el plugin Docker Compose v2. El stack incluye PHP
8.4-FPM, Nginx, MySQL 8.4 y un worker de colas. MySQL y PHP-FPM no publican
puertos al host; Nginx solo escucha en `127.0.0.1:8080` por defecto. Para
publicarlo en Internet, colócalo detrás de un proxy inverso con HTTPS y configura
el dominio real en `APP_URL`.

1. Copia la plantilla y configura contraseñas aleatorias, largas y diferentes
   para el usuario de la aplicación y el administrador de MySQL:

   ```bash
   cp .env.docker.example .env.docker
   ```

2. Genera una clave para esta instalación:

   ```bash
   php artisan key:generate --show
   ```

   Copia la clave completa que imprime el comando en `APP_KEY` dentro de
   `.env.docker`. No reutilices la clave local ni compartas el archivo. Compose
   rechazará las variables secretas vacías.

3. Construye las imágenes e inicia los servicios:

   ```bash
   docker compose --env-file .env.docker build
   docker compose --env-file .env.docker up -d db app web
   ```

4. Ejecuta las migraciones de forma explícita después de que MySQL esté listo:

   ```bash
   docker compose --env-file .env.docker exec app php artisan migrate --force
   docker compose --env-file .env.docker exec app php artisan db:seed --force
   docker compose --env-file .env.docker up -d queue
   ```

   El seeder crea categorías, productos, clientes y proveedores ficticios para
   demostración. El stock inicial queda registrado en movimientos de inventario.
   Es idempotente y no crea ventas ni compras ficticias. No lo ejecutes en una
   base con datos reales salvo que quieras añadir estos ejemplos.

5. Abre `http://localhost:8080/register` para crear la primera cuenta. Revisa
   los servicios y los logs con:

   ```bash
   docker compose --env-file .env.docker ps
   docker compose --env-file .env.docker logs -f app web db queue
   ```

Los datos de MySQL y los archivos subidos se conservan en volúmenes Docker
`database-data` y `app-storage`, incluso al reemplazar los contenedores. No uses
`docker compose down -v` en una instalación con datos: elimina esos volúmenes.
El archivo `.env.docker` está excluido del control de versiones; guarda una
copia segura y respáldalo junto con la base de datos.

Al desplegar una versión nueva, reconstruye y recrea los servicios y aplica las
migraciones de forma controlada:

```bash
docker compose --env-file .env.docker build
docker compose --env-file .env.docker up -d db app web
docker compose --env-file .env.docker exec app php artisan migrate --force
docker compose --env-file .env.docker exec app php artisan db:seed --force
docker compose --env-file .env.docker up -d queue
```

Compose fija `APP_ENV=production` y `APP_DEBUG=false`. Configura un `APP_URL` HTTPS real,
credenciales fuertes, un proveedor de correo real y copias de seguridad
verificadas antes de usar datos de negocio. En instalaciones con TLS, configura
el proxy para redirigir HTTP a HTTPS y activa `SESSION_SECURE_COOKIE=true`.
`MAIL_MAILER=log` es solo el valor inicial para desarrollo; con ese valor, los
enlaces de recuperación no se envían por correo.

## Requisitos

- PHP 8.3 o superior y extensiones requeridas por Laravel, incluido `pdo_mysql`.
- Composer 2.
- Node.js y npm para compilar los recursos.
- MySQL 8 o MariaDB compatible.

Versiones comprobadas durante el desarrollo: PHP 8.4.24, Laravel 13.37.0,
MariaDB 10.4.32 y 11.8.9, Bootstrap 5.3.8, Node.js 24.21.0 y npm 12.0.2.

## Instalación

Desde la carpeta del proyecto:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Edita `.env` y configura `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y
`DB_PASSWORD` con las credenciales del servidor local. No guardes credenciales
reales en `.env.example` ni en el repositorio.

Crea la base de datos y un usuario dedicado desde el cliente MySQL/MariaDB:

```sql
CREATE DATABASE inventorypro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'inventorypro_user'@'127.0.0.1'
    IDENTIFIED BY 'elige-una-clave-larga';

GRANT ALL PRIVILEGES ON inventorypro.* TO 'inventorypro_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Si el servidor autentica la conexión con el host `localhost`, crea también ese
usuario para `localhost` y otórgale los mismos permisos. En XAMPP, utiliza las
credenciales que hayas configurado en su servidor; no uses la cuenta root sin
contraseña fuera de un entorno local desechable.

Ejecuta las migraciones, enlaza el almacenamiento de imágenes y compila el
frontend:

```bash
php artisan migrate
php artisan storage:link
npm run build
php artisan serve
```

Visita `http://127.0.0.1:8000/register` para crear una cuenta y luego inicia
sesión en `/login`. Nunca compartas ni publiques `.env`.

### XAMPP y MariaDB del sistema

Usa un solo servicio de base de datos en el puerto `3306`. Si XAMPP ya está
ejecutando MariaDB, no intentes iniciar en paralelo `mariadb.service`: ambos
servicios compiten por el mismo puerto. En instalaciones con XAMPP bajo
`/opt/lampp`, el servidor se inicia con:

```bash
sudo /opt/lampp/lampp startmysql
```

Si prefieres el servicio MariaDB del sistema, detén primero el servidor XAMPP
desde su panel y después inicia el servicio del sistema:

```bash
sudo systemctl start mariadb
sudo mariadb
```

Si `migrate` informa que no puede conectarse, verifica qué servidor está activo,
las credenciales y los valores `DB_*` de `.env`; después limpia la configuración
cacheada con `php artisan config:clear`. No es necesario borrar ni recrear la
base de datos para corregir credenciales.

## Módulos disponibles

- **Dashboard:** indicadores, ventas recientes, movimientos recientes, productos
  más vendidos y gráfico calculados desde la base de datos.
- **Productos:** búsqueda, filtros, SKU único, categoría, precios, imágenes,
  existencias y archivo/reactivación de productos. El stock se modifica desde
  ajustes, recepción de compras o ventas, no editando el formulario del producto.
- **Categorías, proveedores y clientes:** alta, edición, búsqueda y
  desactivación/reactivación; el historial no se elimina físicamente.
- **Compras:** creación pendiente, recepción de mercancía exactamente una vez y
  cancelación compensatoria.
- **Ventas:** carrito, precios e importes recalculados por el servidor, validación
  de existencias, pago, idempotencia, comprobante y cancelación con devolución.
- **Movimientos:** historial y ajustes de entrada/salida con usuario, motivo y
  balances anteriores y posteriores. No se permite stock negativo.
- **Reportes:** filtros por fecha y exportación de ventas a CSV y PDF.

Las rutas de negocio requieren autenticación. Las operaciones de ventas,
recepción, cancelación y ajustes se procesan con transacciones y bloqueos de
filas pertinentes. Las imágenes se validan por tipo y tamaño; `storage:link`
permite servir los archivos del disco público.

## Configuración

En `.env`, `INVENTORY_CURRENCY` define el código ISO de moneda (por defecto
`PEN`, sol peruano), `INVENTORY_CURRENCY_SYMBOL` el símbolo que se muestra (por
defecto `S/`) e `INVENTORY_TAX_RATE` la tasa porcentual de impuesto (por defecto
`0.00`). Por ejemplo:

```dotenv
INVENTORY_CURRENCY=PEN
INVENTORY_CURRENCY_SYMBOL="S/"
INVENTORY_TAX_RATE=16.00
```

El impuesto y los totales se vuelven a calcular en el servidor; el cálculo del
navegador solo es una vista previa.

## Desarrollo y pruebas

```bash
npm run build
php artisan test
composer check-platform-reqs
npm audit --audit-level=moderate
```

Las pruebas de PHPUnit utilizan SQLite en memoria, por lo que no requieren
MySQL/MariaDB. El servidor de desarrollo puede iniciarse con `php artisan serve`.
