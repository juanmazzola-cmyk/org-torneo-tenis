<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\AdminLogin;
use App\Livewire\Bienvenida;
use App\Livewire\TorneosPublico;
use App\Livewire\Categorias;
use App\Livewire\DrawPublico;
use App\Livewire\Jugadores;
use App\Livewire\RankingPublico;
use App\Livewire\ResultadosPublico;
use App\Livewire\Torneos;
use App\Livewire\TorneoDetalle;
use App\Livewire\DrawBracket;
use App\Livewire\MasterDetalle;
use App\Livewire\MasterPublico;
use App\Livewire\RankingLista;
use App\Livewire\Configuracion;
use App\Livewire\MensajeWhatsapp;
use App\Models\Draw;
use App\Models\Master;
use App\Models\Partido;
use App\Models\Torneo;

// ── Pantalla pública ──────────────────────────────────────────────────
Route::get('/', Bienvenida::class)->name('bienvenida');
Route::get('/live/{torneo}/{draw}',            DrawPublico::class)->name('live.draw');
Route::get('/live/{torneo}/{draw}/resultados', ResultadosPublico::class)->name('live.resultados');
Route::get('/live/{torneo}/master/{master}',   MasterPublico::class)->name('live.master');
Route::get('/ranking-publico',                 RankingPublico::class)->name('ranking.publico');
Route::get('/torneos-publico',                 TorneosPublico::class)->name('torneos.publico');

// ── Auth admin ────────────────────────────────────────────────────────
Route::get('/admin/login', AdminLogin::class)->name('admin.login');
Route::post('/admin/logout', function () {
    session()->forget('admin_auth');
    return redirect()->route('bienvenida');
})->name('admin.logout');

// ── Panel administrador (protegido) ───────────────────────────────────
Route::middleware('admin.auth')->group(function () {
    Route::get('/admin',          fn() => redirect()->route('torneos'));
    Route::get('/jugadores',      Jugadores::class)->name('jugadores');
    Route::get('/categorias',     Categorias::class)->name('categorias');
    Route::get('/torneos',        Torneos::class)->name('torneos');
    Route::get('/ranking',        RankingLista::class)->name('ranking');
    Route::get('/config',         Configuracion::class)->name('config');
    Route::get('/whatsapp',       MensajeWhatsapp::class)->name('whatsapp');

    Route::get('/torneos/{torneo}',                 TorneoDetalle::class)->name('torneo.detalle');
    Route::get('/torneos/{torneo}/draw/{draw}',     DrawBracket::class)->name('torneo.draw');
    Route::get('/torneos/{torneo}/master/{master}', MasterDetalle::class)->name('torneo.master');

    // Vista de impresión (no Livewire)
    Route::get('/torneos/{torneo}/draw/{draw}/imprimir', function (Torneo $torneo, Draw $draw) {
        $partidos = Partido::with(['jugador1', 'jugador2', 'ganador'])
            ->where('draw_id', $draw->id)
            ->orderBy('ronda', 'desc')
            ->orderBy('posicion')
            ->get()
            ->groupBy('ronda');

        return view('draw-imprimir', compact('torneo', 'draw', 'partidos'));
    })->name('torneo.draw.imprimir');
});
