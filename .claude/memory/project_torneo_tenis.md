---
name: Proyecto Torneo de Tenis
description: Descripción completa del proyecto Laravel/Livewire para gestión de torneos de tenis
type: project
---

Proyecto Laravel 12 + Livewire 4 + MySQL (XAMPP) para organizar torneos de tenis.
URL local: http://localhost/org-torneo-tenis/public

**Why:** El usuario quiere gestionar jugadores, categorías, torneos con draws, ranking y configuración.

**How to apply:** Respetar la estructura existente al agregar nuevas funcionalidades.

## Stack
- Laravel 12 (PHP 8.2)
- Livewire 4.2
- Tailwind CSS v4 (vía @tailwindcss/vite)
- MySQL (XAMPP) — BD: torneo_tenis
- maatwebsite/excel para exportar/importar Excel

## Módulos implementados
1. **Categorías** (`/categorias`) — CRUD simple
2. **Jugadores** (`/jugadores`) — CRUD con búsqueda, filtro, exportar Excel, ícono WhatsApp
3. **Torneos** (`/torneos`) — lista de torneos
4. **Detalle Torneo** (`/torneos/{id}`) — inscripciones (con WhatsApp, 2 categorías por jugador, Excel) + configuración de draws
5. **Draw Bracket** (`/torneos/{id}/draw/{drawId}`) — bracket visual, cargar resultados, ranking automático
6. **Ranking** (`/ranking`) — lista con filtros, exportar Excel
7. **Configuración** (`/config`) — datos del club, puntos por ronda

## Archivos clave
- Componentes Livewire: `app/Livewire/`
- Vistas: `resources/views/livewire/`
- Modelos: `app/Models/`
- Exports: `app/Exports/`
- Rutas: `routes/web.php`
- BD: `database/migrations/2024_01_01_*`

## Pendiente / solicitado por el usuario
- Config: más datos a agregar conforme el usuario los pida
