# GarageMap (con inicio de sesión) — TP Evaluatorio Nº 2 (Programación Web Dinámica)

Intermediario entre vendedores y clientes de cocheras/estacionamientos.

- **Cliente (sin cuenta):** ve las cocheras cercanas a su ubicación, ordenadas por distancia, con filtro por radio, y mira el detalle.
- **Vendedor (con cuenta):** se registra, publica sus cocheras marcando la ubicación en un mapa, y **solo él** puede editarlas o borrarlas.

**Funcionalidad con librería externa:** geolocalización y mapas interactivos
(**MapLibre GL JS + OpenFreeMap**, geocodificación con **Nominatim**).

---

## 1. Instalación

Requisitos: **PHP 8.3+** (Laravel 13), Composer, MySQL/MariaDB. En Windows, Laragon trae todo.

> **Usá una carpeta y una base NUEVAS** para no pisar la versión sin login:
> si copiás estos archivos sobre el proyecto anterior, queda una migración vieja (`add_ip_to_garages_table`)
> que rompe `migrate`, y `migrate:fresh` borraría tus datos.

```bash
# 1. Base nueva (desde la terminal de Laragon)
mysql -u root -e "CREATE DATABASE garagemap_login CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Proyecto Laravel 13 limpio
cd C:\laragon\www
composer create-project laravel/laravel garagemap-login
cd garagemap-login

# 3. Copiar encima las carpetas de este paquete: app/, database/, public/, resources/, routes/
#    (aceptar reemplazar routes/web.php y database/seeders/DatabaseSeeder.php)

# 4. En .env:
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=garagemap_login
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Tablas + datos de demostración, enlace de fotos y servidor
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Abrir `http://127.0.0.1:8000`.

**Usuarios de demostración** (contraseña de ambos: `password123`):

| Usuario | Email |
|---|---|
| Vendedor Uno | `vendedor1@example.com` |
| Vendedor Dos | `vendedor2@example.com` |

Las 8 cocheras de ejemplo están repartidas entre los dos.

> La búsqueda por cercanía usa funciones SQL de MySQL/MariaDB (`acos`, `cos`, `radians`, `least`): **no funciona con SQLite**.
> La geolocalización del navegador solo funciona en `localhost`/`127.0.0.1` o HTTPS.
> El mapa necesita internet (MapLibre desde unpkg y los mapas de OpenFreeMap). MapLibre dibuja con **WebGL**: el navegador debe soportarlo.
> Laravel 13 renombró el middleware de CSRF a `PreventRequestForgery`; `@csrf` y el token se usan igual.

---

## 2. Estructura (MVC)

| Capa | Archivos |
|---|---|
| **Rutas** | `routes/web.php` |
| **Controllers** | `GarageController` (buscar, publicar, editar, borrar), `AuthController` (registro, ingreso, salida) |
| **Policy** | `app/Policies/GaragePolicy.php` (solo el dueño edita o borra) |
| **Models** | `Garage`, `Foto`, `Caracteristica` (y el `User` que trae Laravel) |
| **Migrations** | `garages` (con `user_id`), `fotos`, `caracteristicas`, pivote `caracteristica_garage` |
| **Seeders** | `UserSeeder`, `CaracteristicaSeeder`, `GarageSeeder`, `DatabaseSeeder` |
| **Vistas Blade** | `layouts/app`, `auth/login`, `auth/register`, `garages/index`, `show`, `mias`, `create`, `edit`, `_form` |
| **JS (cliente)** | `public/js/garages-index.js`, `public/js/garages-create.js` |

| Método | URL | Quién | Qué hace |
|---|---|---|---|
| GET | `/` | Todos | Pantalla del cliente (mapa + lista) |
| GET | `/api/garages/cercanas?lat&lng&radio` | Todos | **JSON** con cocheras en el radio (AJAX) |
| GET | `/garages/{garage}` | Todos | Detalle |
| GET/POST | `/registro` | Invitados | Crear cuenta |
| GET/POST | `/login` | Invitados | Ingresar (máx. 5 intentos/min) |
| POST | `/logout` | Con sesión | Salir |
| GET | `/mis-cocheras` | Con sesión | Mis publicaciones |
| GET/POST | `/garages/create`, `/garages` | Con sesión | Publicar |
| GET/PUT/DELETE | `/garages/{garage}/edit`, `/garages/{garage}` | **Solo el dueño** | Editar y borrar |

---

## 3. Qué aporta la librería y qué desarrolló el grupo

| Aporta la librería (MapLibre / OpenFreeMap / Nominatim / navegador) | Desarrollado por el grupo |
|---|---|
| MapLibre dibuja el mapa vectorial; OpenFreeMap provee el estilo y los datos | Modelo de datos y migraciones (garages, fotos, características, pivote) |
| Marcadores, popups, marcador arrastrable, zoom y vuelos de cámara | Scope `cercanas()`: **Haversine en SQL** con parámetros vinculados |
| Convertir dirección → coordenadas (Nominatim) | Endpoint JSON, validación y respuesta con solo los campos necesarios |
| Obtener la ubicación del usuario (Geolocation API del navegador) | Flujo AJAX: `fetch`, debounce, `AbortController`, estados cargando/vacío/error |
| | Registro, login y **autorización por dueño** (Policy) |
| | Publicación y edición con fotos (validación y `Storage`) |

---

## 4. Contenidos del curso que se usan

| Episodio | Dónde |
|---|---|
| Ep. 5 — Migrations, Models, Seeders, `$fillable`, `migrate:fresh --seed` | `database/`, `Garage::$fillable` |
| Ep. 6 — Relaciones 1:N y N:N (`hasMany`, `belongsTo`, `belongsToMany`, `sync`) | `Garage` → `User`, `Garage` ↔ `Foto`, `Garage` ↔ `Caracteristica` |
| Ep. 6 — Accessors, casts | `precio_formateado`, casts de `latitud`/`disponible` |
| Ep. 6 — Scopes | `disponibles()`, `cercanas()` |
| Ep. 6 — Eager loading | `->with('fotos')` en `cercanas` y `mias`, `load()` en `show`/`edit` |
| Ep. 7 — AJAX, `fetch`, JSON, DOM, debounce, `AbortController` | `garages-index.js` |
| Ep. 7 — Validación en servidor, códigos HTTP | `reglas()` en `GarageController`, 403 de la Policy |
| Ep. 7 Bis — Autenticación (`auth`, `guest`) | `routes/web.php`, `AuthController` |
| Ep. 7 Bis — Autorización (Policy, `Gate::authorize`) | `GaragePolicy`, `edit`/`update`/`destroy` |
| Ep. 7 Bis — CSRF, XSS, SQL Injection, mass assignment | `@csrf`, `{{ }}`, parámetros `?`, `$fillable` sin `user_id` |
| Ep. 7 Bis — Subida de imágenes | `image`, `mimes`, `max:2048`, `store()` con nombre propio, `Storage` |

> Aviso: la autenticación se ve en el **próximo** episodio. Esta versión la resuelve a mano con `Auth::attempt`,
> `Hash::make`, middleware `auth`/`guest` y una Policy, sin paquetes extra, para poder explicar cada línea.

---

## 5. Login y autorización: cómo funciona

1. **Registro:** valida (nombre, email único, contraseña de 8+ con confirmación), guarda la contraseña **hasheada** (`Hash::make`, bcrypt) e inicia sesión.
2. **Ingreso:** `Auth::attempt()` compara contra el hash. Si falla, el mensaje es el mismo para email inexistente y contraseña incorrecta (no revela cuál). `throttle:5,1` frena la fuerza bruta.
3. **Sesión:** se llama a `session()->regenerate()` al entrar (evita fijación de sesión) y a `invalidate()` + `regenerateToken()` al salir. Salir es un `POST` con CSRF.
4. **Rutas protegidas:** el middleware `auth` manda a `/login` a quien no tiene sesión y, al ingresar, `intended()` lo devuelve a donde iba.
5. **Autorización:** estar logueado no alcanza. `GaragePolicy` compara `user->id` con `garage->user_id`, y el controller llama a `Gate::authorize()` en `edit`, `update` y `destroy`. Un usuario ajeno recibe **403**, aunque escriba la URL a mano. Los botones de la vista se ocultan con `@can`, pero eso es solo comodidad: **la verificación real está en el servidor** (Ep. 7 Bis).
6. **El dueño no viene del formulario:** `user_id` no está en `$fillable` y se asigna con `$request->user()->id`. Nadie puede publicar "a nombre de otro" manipulando el HTML.
7. **Fotos ajenas:** al editar, solo se pueden quitar fotos cuyo `garage_id` sea el de esa cochera, aunque se manden ids inventados.

---

## 6. Alternativas investigadas (criterios 20 % y 10 %)

| | Leaflet + OSM | **MapLibre GL JS + OpenFreeMap** | Google Maps Platform | Mapbox GL JS |
|---|---|---|---|---|
| Costo / alta | Gratis, sin API key | Gratis, sin API key ni registro | API key y cuenta de facturación | Token; plan gratuito con cuota |
| Tipo de mapa | Imágenes (raster), simple | Vectorial (WebGL), zoom fluido | Vectorial, muy completo | Vectorial, muy completo |
| Integración con Laravel | Un `<link>` y un `<script>` en Blade | Módulo ES + import map en el layout | Script con key | Script + token |
| Riesgo en la demo | Bajo | Bajo (necesita WebGL) | Cuota/facturación/key | Cuota/token |

**Elegimos MapLibre GL JS + OpenFreeMap** por ser gratuito, sin API key ni registro, y de código abierto. MapLibre solo dibuja: el estilo y los datos del mapa los pone OpenFreeMap (que se basa en datos de OpenStreetMap). Leaflet era la alternativa más simple, pero MapLibre da un mapa vectorial más fluido.
*Verificar precios y cuotas vigentes de Google y Mapbox en sus sitios antes de presentar: cambian.*

**Limitaciones:**
- OpenFreeMap y Nominatim públicos tienen política de uso razonable (Nominatim: 1 consulta/seg, sin autocompletado). Para mucho tráfico se usaría un proveedor propio.
- Se necesita WebGL en el navegador.
- La geocodificación de OSM es menos precisa que la de Google en algunas direcciones.
- Haversine en SQL recorre la tabla (sin índice espacial): con miles de filas conviene un bounding box previo o tipos espaciales de MySQL.
- Cualquiera puede crear una cuenta: no hay verificación de email ni captcha, así que un usuario malintencionado puede registrar cuentas y publicar cocheras falsas. En producción se agregarían verificación de email, captcha y moderación.
- No hay recuperación de contraseña ni cambio de contraseña.

**Otros dominios donde sirve la misma funcionalidad:** turnos médicos por sucursal cercana,
comercios/farmacias de turno, delivery y zonas de cobertura, eventos cercanos, seguimiento de recorridos.

---

## 7. Guía para la exposición

1. **Problema:** encontrar cochera cerca de donde estoy y publicar la propia gestionándola solo yo.
2. **Herramienta:** qué es MapLibre, de dónde salen los mapas (OpenFreeMap), qué hace Nominatim, por qué descartamos Google/Mapbox y qué hace distinto a Leaflet.
3. **Integración:** CSS de MapLibre e import map en el layout Blade, el `<div id="mapa">`, el JS que llama al endpoint.
4. **Demo:** buscar cerca → cambiar radio → clic en otro punto → ver detalle → ingresar como Vendedor Uno → "Mis cocheras" → editar una → intentar editar una de Vendedor Dos escribiendo la URL (403) → publicar con foto y ubicación → borrar.
5. **Dificultades:** *(completar con las reales del grupo)*, por ejemplo permisos de geolocalización, SQLite sin funciones matemáticas, autorización por dueño, `user_id` fuera de `$fillable`.
6. **Otros dominios** (sección 6).

**Preguntas probables de la docente**

- ¿Diferencia entre autenticación y autorización? → "¿quién sos?" (`auth`, login) vs "¿podés hacerlo?" (`GaragePolicy`).
- ¿Un usuario autenticado puede no estar autorizado? → Sí: Vendedor Uno logueado intentando editar una cochera de Vendedor Dos recibe 403.
- Si el botón Editar solo se ve para el dueño, ¿está resuelto? → No: el cliente es modificable; el servidor verifica con `Gate::authorize`.
- ¿Por qué `user_id` no está en `$fillable`? → mass assignment: si estuviera, un formulario manipulado podría asignar el dueño.
- ¿Por qué se hashea la contraseña? → si se filtra la base, no se filtran las contraseñas. Se verifica con `Auth::attempt`, no se recupera.
- ¿Por qué Haversine y no distancia plana? → la Tierra es esférica; `Garage::cercanas()`.
- ¿Por qué `?` y no concatenar? → SQL Injection.
- ¿Por qué `least(1, …)`? → el redondeo puede dar un valor apenas mayor a 1 y `acos` devolvería NULL.
- ¿Qué pasa si cambio el radio varias veces rápido? → debounce + `AbortController`.
- ¿Por qué no `innerHTML` en los popups? → XSS; se usa `textContent`.
- ¿Qué evita el N+1? → `with('fotos')`.
- ¿Qué hace la librería y qué hicieron ustedes? → sección 3.
- ¿Por qué `[lng, lat]` y no `[lat, lng]`? → MapLibre sigue el orden GeoJSON (x, y); en la base y en Haversine usamos latitud y longitud por separado.
- ¿Cómo dibujan el radio? → MapLibre no trae un círculo en metros: generamos un polígono GeoJSON de 64 lados con trigonometría esférica.

---

*Datos de demostración (direcciones, nombres, emails, teléfonos) son inventados.*
