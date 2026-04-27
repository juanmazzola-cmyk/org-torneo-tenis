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
