# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install deps, generate key, migrate DB, build assets
composer run-script setup

# Start dev server (Laravel + queue + logs + Vite hot reload concurrently)
# NOTA: en Windows esto falla — `php artisan pail` requiere la extensión `pcntl`,
# que no existe en PHP para Windows, y `concurrently --kill-others` mata todos los
# demás procesos (server, queue, vite) cuando pail crashea. En Windows usar en su
# lugar `php artisan serve` solo (elegir un puerto libre si el 8000 ya está en uso
# por otro proyecto local).
composer run dev

# Run tests (clears config cache first)
composer run test

# Production asset build
npm run build
```

## Architecture

**Stack:** Laravel 12 + Livewire 4 + Tailwind CSS 4 + Vite. SQLite database (local dev). No Eloquent ORM relationships are heavily used — raw queries and service classes handle most logic.

### Two-panel structure

- **Admin panel** (`/admin/*` routes, guarded by `AdminAuth` middleware): CRUD for players, categories, tournaments, draws, rankings, config. Session-based custom auth (not Laravel Breeze/Sanctum).
- **Public panel** (`/`, `/live/*`, `/torneos-publico`, `/ranking-publico`): Read-only views, embeddable via iframe, PWA-installable.

### Key directories

| Path | What lives here |
|---|---|
| `app/Livewire/` | All Livewire components — prefix `Admin*` for admin, bare names for public |
| `app/Models/` | Torneo, Partido, Draw, Master, MasterGrupo, MasterPartido, MasterFinal, Jugador, Inscripcion, Categoria, Ranking, Config |
| `app/Services/RankingService.php` | Ranking calculation logic |
| `app/Imports/`, `app/Exports/` | Maatwebsite/Excel import/export classes |
| `resources/views/livewire/` | Blade templates for Livewire components |
| `resources/views/layouts/` | App and public layout shells |

### Tournament types

1. **Regular tournaments** — Draw-based brackets (`Draw`, `Partido` models).
2. **Master tournaments** — Round-robin zones (`MasterGrupo`, `MasterJugadorGrupo`, `MasterPartido`) followed by a knockout final (`MasterFinal`). Managed via `MasterDetalle` Livewire component.

#### Master — retiro por lesión con suplente

Botón "Retirar" en cada jugador de la zona (`MasterDetalle::abrirRetirar()` / `retirarJugador()`):

- El lesionado queda en la zona con `master_jugadores_grupo.retirado = 1`: sus partidos **jugados** se mantienen y suman en la tabla, pero `calcularPosiciones()` lo manda siempre al final, no clasifica (`generarFaseFinal()` filtra retirados) y recibe los 40 pts de no clasificado en el ranking.
- El suplente (inscripto en la categoría y fuera de las zonas) entra como fila nueva con `posicion = max + 1` (5) y `reemplaza_a_id` = id del lesionado. Ocupa su lugar en los partidos **pendientes** (`ganador_id` null) de J2/J3. La tabla queda con 5 filas.
- **El reemplazo se guarda en `master_jugadores_grupo`, no en los partidos**, porque `actualizarCruces()`/`limpiarCruces()` reescriben J2/J3. `MasterGrupo::aplicarReemplazos()` se vuelve a llamar al final de `actualizarCruces()`, así un recálculo nunca vuelve a poner al lesionado.
- Restricciones: solo con la J1 de la zona completa, sin fase final generada, y el lesionado con al menos un partido pendiente. Con un retirado en la zona: no se puede Cambiar/Quitar jugadores, y la J1 no se puede editar/anular si ya hay resultados en J2/J3 (`jornada1Bloqueada()`; se muestra 🔒).
- No hay "reincorporar": deshacer un retiro es manual en la base.

#### Torneo de dobles (Master con otro formato)

Se reutiliza el Master: el torneo se crea como tipo `master` y la categoría lleva **`es_dobles`** (formato y vista) y **`sin_ranking`** (no suma puntos). Cada pareja es **un jugador** con apellido `"Pérez / Gómez"` (siempre con espacios alrededor de la barra: `Jugadores` aplica `ucwords(strtolower())`, que solo pone mayúscula después de un espacio). Todo lo nuevo pasa por `categoria->es_dobles` o por "zona con 3 partidos" (que solo existe en dobles): el Master normal no cambia.

- **Zonas:** al crear el Master (`TorneoDetalle::crearMaster()`) se elige 2, 3 o 4 zonas (`Master::crearGrupos($n)`); las parejas se asignan a mano, 3 o 4 por zona. La zona de 4 funciona igual que el Master. La de 3 se genera con el botón "Generar Partidos (zona de 3)": calendario **fijo** J1 1-2, J2 2-3, J3 1-3 (en orden de posición), descansa una pareja por jornada (`MasterGrupo::descansos()`).
- **Gotcha — tamaño de zona:** `MasterGrupo::esDeTres()` cuenta **partidos** (3), no filas de `master_jugadores_grupo`: con un retiro una zona de 3 tiene 4 filas. En zona de 3 `limpiarCruces()` no hace nada (borraría J2/J3 para siempre, porque no hay cruces que rearmar) y `jornada1Bloqueada()` es siempre falso.
- **Clasificación** (`Master::clasificacionDobles()`, la usan admin y público; siempre clasifican 4, los retirados nunca):
  - 2 zonas: 1° y 2° de cada una; semis como el Master (1°Z1-2°Z2, 1°Z2-2°Z1).
  - 3 zonas: los 3 ganadores + el **mejor segundo por promedios** (`MasterGrupo::promedios()` / `compararPromedios()`): puntos por partido jugado, después % de sets y % de games, con todos los partidos jugados (con retiro, cada pareja con los que jugó). El **W.O. cuenta como 6-0 6-0 solo en esa comparación**; la tabla de zona no cambia. Semis: el mejor segundo contra el mejor ganador de **otra** zona (ganadores ordenados por el mismo promedio; empate → número de zona), los otros dos ganadores entre sí.
  - 4 zonas: el 1° de cada una; semis Z1-Z2 y Z3-Z4.
- **Empate total entre mejores segundos:** "Generar Fase Final" se frena y abre un modal para elegir a mano (sorteo); `generarFaseFinal($id)` solo acepta un id de los empatados.
- **Retiro con suplente** funciona igual que en el Master (en zona de 3, "J1 completa" = su único partido). **"Cambiar/Asignar" está bloqueado en zonas de 3 con partidos generados** (no se regeneran solos): quitar la pareja (✕) y volver a asignar.
- **Vistas:** en dobles se muestra solo el apellido de la pareja (`@unless($dobles), nombre@endunless`); la tabla de posiciones deja que el nombre baje de línea (`leading-tight`) y en el admin oculta SG/SP/GG/GP en celular (`hidden sm:table-cell`). Con 3 zonas se muestra la tabla "Mejor segundo" (provisoria hasta completar las zonas). En listados públicos dice "Dobles" en vez de "Master".

### Ranking — caché

`RankingService::calcular()` cachea el resultado **5 minutos** (clave por combinación de filtros) y **nadie la invalida** al cargar resultados (ni `MasterDetalle` ni `DrawBracket`). Si una columna/puntos nuevos "no aparecen" en Ranking (admin o público) justo después de cargar una final, primero esperar 5 minutos y recargar antes de sospechar un bug — la tabla `rankings` normalmente ya está bien. No hay columna que marque filas de Master: se distinguen por `torneos.tipo = 'master'`, y en pantalla el Master es una columna más con el nombre del torneo. Los desempates por sets/games solo cuentan partidos de draws (`Partido`), no de Master.

**Categorías sin ranking / dobles:** `categorias.sin_ranking` hace que `recalcularRankingMaster()` borre las filas y no escriba puntos, y saca la categoría de los filtros del Ranking (admin, público y panel de Bienvenida; el panel Torneos la sigue mostrando). No se puede marcar si la categoría ya tiene filas en `rankings`. `categorias.es_dobles` marca que cada jugador es una pareja: en la inscripción (individual y masiva) una categoría de dobles solo lista parejas y las demás solo jugadores individuales (validado también en el servidor).

### Authentication

Custom `AdminAuth` middleware checks `session('admin_logged_in')`. The `AdminLogin` Livewire component handles login. There is no `User` model used for auth.

### Routing

Routes are in `routes/web.php`. Admin routes use the `admin.auth` middleware alias. The public live draw is served at `/live/{torneo}/{draw}` and is designed to be loaded inside an iframe.

### Deploy

Push to `main` → GitHub Actions calls a Ferozo webhook (`FEROZO_WEBHOOK_URL` secret) which pulls and deploys on the hosting server. **Hoy el webhook no dispara solo: después del push el deploy se hace a mano (ver "Estado conocido" abajo).**

**IMPORTANTE:** Ferozo no tiene Node.js, así que **cualquier clase de Tailwind nueva (no solo las ya usadas antes en el proyecto) necesita `npm run build` antes de commitear**, o simplemente no existe en producción — el sitio sirve el CSS ya compilado en `public/build`, no lo genera al vuelo. Un layout roto/sin estilo tras un deploy que "corrió bien" suele ser justamente esto, no un bug de lógica.

**OJO — `resources/css/app.css` también escanea `storage/framework/views/*.php`** (vistas compiladas, incluidas las de paquetes). Si se corre `php artisan view:clear` antes de `npm run build`, el CSS sale más chico y **pierde clases que hoy están en producción**. Antes de compilar: `php artisan view:cache`, y después comparar que el CSS nuevo no haya perdido clases respecto del commiteado.

**IMPORTANTE:** Nunca hacer `git push --force` a `main`. El webhook solo ejecuta `git pull` en el servidor — un force push rompe el estado git de Ferozo y la próxima actualización falla silenciosamente. Si el servidor queda desincronizado, los archivos deben actualizarse manualmente vía el Administrador de Archivos de DonWeb.

**IMPORTANTE — archivos subidos manualmente por WinSCP:** Si en algún momento se subió un archivo manualmente por WinSCP (porque el webhook no lo deployó), git en Ferozo lo ve como "cambio local" y bloquea el próximo `git pull` con el error `Your local changes would be overwritten by merge`. Los archivos que históricamente han causado este problema: `app/Livewire/AdminAnalytics.php`, `resources/views/livewire/admin-analytics.blade.php`, `resources/views/livewire/bienvenida.blade.php`, `resources/views/livewire/configuracion.blade.php`, `routes/web.php`, `app/Http/Controllers/BannerUploadController.php` (untracked).

**OJO — volver a subir la versión "actual" por WinSCP NO alcanza si el HEAD del servidor quedó atrasado varios commits.** El error de git compara el archivo en disco contra el commit en el que está parado el HEAD del servidor, no contra el último commit de `origin/main`. Si el HEAD del servidor quedó viejo (ej. atascado en un commit de hace varias features), subir la versión más nueva de un archivo sigue marcándolo como "modificado" para siempre, porque nunca va a coincidir con lo que git espera ahí. En ese caso la única solución real es la recuperación completa de más abajo (borrar la carpeta y re-clonar), no seguir subiendo archivos a mano.

**Estado conocido (2026-10-04):** el deploy funciona (el `git pull` en el servidor ya no aborta; el problema del HEAD clavado en `43e458e` de 2026-07-17 quedó resuelto), **pero el webhook no dispara automáticamente** — aunque el GitHub Action quede en verde, el pull no se ejecuta solo. Después de cada push a `main` hay que disparar el deploy **a mano** desde **DonWeb → Mi Sitio Web → GIT → Deploy**. No hace falta subir archivos por WinSCP.

#### Acceso al servidor

El servidor de Ferozo **no tiene terminal SSH disponible**. El acceso a archivos se hace vía **WinSCP** (FTP) o el Administrador de Archivos de DonWeb. No hay forma de correr comandos artisan ni git directamente en producción.

La base de datos en producción es **MySQL** (no SQLite). Las credenciales están en el `.env` del servidor.

**Migraciones:** como no hay `artisan` en producción, cada migración nueva se aplica a mano como SQL crudo en phpMyAdmin de DonWeb, **antes** de disparar el deploy (si el código llega primero, falla al leer columnas inexistentes). Además insertar la fila en `migrations` para mantenerla consistente: `INSERT INTO migrations (migration, batch) SELECT '<nombre_archivo_sin_.php>', COALESCE(MAX(batch),0)+1 FROM migrations;`. Orden: SQL → push → deploy manual.

#### Recuperación del deploy roto (git divergido)

Si el deploy deja de funcionar con error "divergent branches" o similar, el procedimiento es:

1. **Backup vía WinSCP**: copiar `.env`, descargar `public/banners/` (SQLite no aplica, usan MySQL)
2. **Eliminar** la carpeta `/public_html/torneos/` completa
3. **Re-integrar** desde DonWeb → Mi Sitio Web → GIT (eliminar repo existente, crear nuevo apuntando a `/public_html/torneos`, rama `main`)
4. **Restaurar** el `.env` y las imágenes de `public/banners/` vía WinSCP

#### Banner de imagen — caché del browser

Al actualizar la imagen del banner, si el nombre del archivo no cambia el browser sirve la versión cacheada. Para forzar la actualización en todos los visitantes, renombrar el archivo (ej. `banner2.jpg`) y actualizar la URL en Config.

### Config (clave-valor)

El modelo `Config` (`app/Models/Config.php`) guarda configuración en la tabla `configs` como pares clave/valor, con cache de 1 hora (`Cache::remember('app_config', 3600, ...)`). `Config::set()` invalida el cache automáticamente.

Claves actuales: `club_nombre`, `club_ciudad`, `club_telefono`, `puntos_campeon`, `puntos_subcampeon`, `puntos_semifinal`, `puntos_cuartos`, `puntos_octavos`, `puntos_16avos`, `puntos_32avos`, `admin_code`, `panel_info`, `banner_url`.

La clave `panel_info` se muestra **siempre visible** en la pantalla pública principal (debajo del banner), con fondo vidrio esmerilado y título "ℹ️ Información". No requiere que el usuario haga click para verla.

La clave `banner_url` contiene la URL de la imagen de banner que se muestra en la pantalla pública debajo del nombre del club. Se actualiza automáticamente al subir una imagen desde el panel Config (ver abajo). También se puede editar manualmente para apuntar a una URL externa.

#### Upload de banner desde el panel admin

Ruta: `POST /admin/banner-upload` (protegida por `admin.auth`), controlador `app/Http/Controllers/BannerUploadController.php`.

- Guarda la imagen en `public/images/banner-torneo.{ext}` usando `move()` directamente (sin Storage ni symlinks).
- Elimina el banner anterior con `glob()` + `unlink()` antes de guardar el nuevo.
- Actualiza `Config::set('banner_url', ...)` con `asset()` + `?v=timestamp` para romper caché de browser.
- El directorio `public/images/` está en el repo con un `.gitkeep`; su contenido está ignorado en `.gitignore`.

**Por qué el form de upload está fuera del `<form wire:submit>`:** Livewire 4 intercepta todos los `submit` del form raíz. Anidar un `<form enctype="multipart/form-data">` dentro rompe el upload. La solución es tener ambos forms como elementos hermanos en el blade, no anidados.

### Galería de fotos

Tabla `galeria_fotos` (`id`, `filename`, `descripcion` nullable, `orden`, timestamps), modelo `GaleriaFoto` (accessor `url` → `asset('images/galeria/'.$filename)`).

**Upload** (`POST /admin/galeria-upload`, `GaleriaUploadController`): mismo patrón que el banner (form HTML clásico, hermano del `wire:submit`, sin Storage ni symlinks), pero además comprime cada imagen con GD antes de guardarla:
- Corrige la orientación según el EXIF (las fotos de celular vienen con un flag de rotación, no rotadas en los píxeles).
- Aplana transparencia (PNG/WebP) sobre fondo blanco.
- Redimensiona a máx. 1920px de ancho manteniendo la proporción.
- Guarda siempre como `.jpg` a calidad 85%, sin importar el formato subido.
- La validación no tiene límite de tamaño (`fotos.*` solo valida `image|mimes:jpg,jpeg,png,webp`) — el límite real lo maneja la compresión, no el peso del archivo original.
- `public/.user.ini` sube `upload_max_filesize`/`post_max_size`/`memory_limit` para que PHP acepte el archivo original grande antes de procesarlo (funciona si Ferozo corre PHP-FPM; si no, se ignora sin romper nada).

**Panel admin**: ruta `/galeria`, componente `AdminGaleria` — listado + edición inline de descripción + eliminar.

**Panel público**: dentro de `Bienvenida` (panel `'galeria'`, método `abrirGaleria()`). Grid responsivo (2/3/4 columnas según viewport) con miniaturas `aspect-[4/5]` + `object-cover` (no cuadradas: un recorte cuadrado cortaba cabeza/pies en fotos verticales tipo retrato), y lightbox hecho con Alpine.js (viene incluido con Livewire, no se instaló nada externo) con navegación anterior/siguiente y cierre tocando afuera o con teclado.

**Gotcha Alpine + Livewire:** el `x-data` del lightbox tiene su propio método para cerrarse — **nunca nombrarlo igual que una acción Livewire del mismo componente** (ej. `cerrar()`). El botón "← Volver" del panel usa `wire:click="cerrar"`, y un método de Alpine con el mismo nombre en un `x-data` ancestro puede pisar esa resolución silenciosamente (sin error visible en consola). Se usa `cerrarLightbox()` justamente para evitar esa colisión.

**Uso previsto:** el club sube fotos por torneo y las borra manualmente después de cada uno — no es un archivo histórico acumulativo (para no llenar el disco del hosting compartido).

### PWA — instalación

Archivos: `public/manifest.json`, `public/sw.js`, íconos `public/icon-192.png` y `public/icon-512.png`. El layout `resources/views/layouts/publica.blade.php` incluye el `<link rel="manifest">` y registra el service worker.

El banner de instalación está en `resources/views/livewire/bienvenida.blade.php`:
- **Banner flotante** (`#pwa-banner`): aparece en la parte inferior de la pantalla.
- **Botón inline** (`#pwa-install-inline`): aparece en la grilla de botones de la pantalla principal.

Ambos son `display:none` por defecto y se muestran **únicamente cuando Chrome dispara el evento `beforeinstallprompt`** (requiere HTTPS y que el browser considere que el usuario tiene suficiente engagement con el sitio). En local (HTTP/XAMPP) el evento nunca se dispara — es una limitación del browser, no del código.

El usuario puede descartar el banner flotante con el botón ✕; la decisión se guarda en `localStorage` con la clave `pwa_banner_dismissed` y no vuelve a aparecer.

Para probar el banner visualmente en local sin modificar código: abrir DevTools → Console y ejecutar `document.getElementById('pwa-banner').style.display='block'`.

### Google Analytics 4

El snippet de GA4 (propiedad `G-40QFQ0JL8K`) está incluido en **ambos layouts**:
- `resources/views/layouts/app.blade.php` (panel admin)
- `resources/views/layouts/publica.blade.php` (panel público)

#### Sección Analytics en el panel admin

Ruta: `/admin/analytics` (protegida por `admin.auth`), componente `app/Livewire/AdminAnalytics.php`.

Muestra un embed de **Looker Studio** con 3 scorecards: visitas hoy, este mes, este año. El reporte está conectado a la propiedad GA4 `G-40QFQ0JL8K` y se actualiza cada ~15 minutos.

**Por qué Looker Studio y no la GA4 Data API directa:** se intentó usar la GA4 Data API con una service account (`analytics-torneos@torneos-tenis-mercedes.iam.gserviceaccount.com`), pero GA4 no acepta service accounts como usuarios vía UI, y el GCP project link no estaba disponible en el plan. Looker Studio no requiere service account.

### Livewire 4 — limitaciones conocidas

- **File uploads**: Livewire 4 intercepta todos los eventos `submit` dentro del componente raíz, incluyendo formularios HTML nativos. Los uploads de archivos vía `WithFileUploads` y también vía controladores externos desde dentro de un componente Livewire son problemáticos en este entorno (XAMPP + Ferozo). Solución adoptada: usar un controlador Laravel clásico con un `<form>` HTML independiente (hermano, no anidado) respecto al `<form wire:submit>`. Ver `BannerUploadController` como referencia.
- **`Storage::url()`** genera URLs relativas (`/storage/...`) incorrectas en XAMPP con subcarpeta. Usar siempre `asset()` para archivos públicos locales.
- El symlink `storage:link` no se ejecuta en el deploy de Ferozo (solo hace `git pull`).
