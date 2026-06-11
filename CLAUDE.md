# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install deps, generate key, migrate DB, build assets
composer run-script setup

# Start dev server (Laravel + queue + logs + Vite hot reload concurrently)
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

### Authentication

Custom `AdminAuth` middleware checks `session('admin_logged_in')`. The `AdminLogin` Livewire component handles login. There is no `User` model used for auth.

### Routing

Routes are in `routes/web.php`. Admin routes use the `admin.auth` middleware alias. The public live draw is served at `/live/{torneo}/{draw}` and is designed to be loaded inside an iframe.

### Deploy

Push to `main` → GitHub Actions calls a Ferozo webhook (`FEROZO_WEBHOOK_URL` secret) which pulls and deploys on the hosting server.

**IMPORTANTE:** Nunca hacer `git push --force` a `main`. El webhook solo ejecuta `git pull` en el servidor — un force push rompe el estado git de Ferozo y la próxima actualización falla silenciosamente. Si el servidor queda desincronizado, los archivos deben actualizarse manualmente vía el Administrador de Archivos de DonWeb.

**IMPORTANTE — archivos subidos manualmente por WinSCP:** Si en algún momento se subió un archivo manualmente por WinSCP (porque el webhook no lo deployó), git en Ferozo lo ve como "cambio local" y bloquea el próximo `git pull` con el error `Your local changes would be overwritten by merge`. La solución es volver a subir esos mismos archivos por WinSCP con la versión más reciente del repo local — cuando el contenido coincide con el commit actual, git deja de verlos como modificados. Los archivos que históricamente han causado este problema: `app/Livewire/AdminAnalytics.php`, `resources/views/livewire/admin-analytics.blade.php`, `resources/views/livewire/bienvenida.blade.php`.

#### Acceso al servidor

El servidor de Ferozo **no tiene terminal SSH disponible**. El acceso a archivos se hace vía **WinSCP** (FTP) o el Administrador de Archivos de DonWeb. No hay forma de correr comandos artisan ni git directamente en producción.

La base de datos en producción es **MySQL** (no SQLite). Las credenciales están en el `.env` del servidor.

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

La clave `banner_url` contiene una URL de imagen externa que se muestra en la pantalla pública debajo del nombre del club. El usuario sube la imagen directamente al servidor vía DonWeb (carpeta `public/banners/`) y pega la URL en Config. No hay upload desde la app — Livewire 4 tiene problemas con file uploads en este entorno.

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

- **File uploads**: Livewire 4 intercepta todos los eventos `submit` dentro del componente raíz, incluyendo formularios HTML nativos. Los uploads de archivos vía `WithFileUploads` y también vía controladores externos desde dentro de un componente Livewire son problemáticos en este entorno (XAMPP + Ferozo). Solución adoptada: el usuario sube imágenes directamente al servidor por FTP/panel de hosting y pega la URL en Config.
- **`Storage::url()`** genera URLs relativas (`/storage/...`) incorrectas en XAMPP con subcarpeta. Usar siempre `asset()` para archivos públicos locales.
- El symlink `storage:link` no se ejecuta en el deploy de Ferozo (solo hace `git pull`).
