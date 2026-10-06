<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterJugadorGrupo extends Model
{
    protected $table = 'master_jugadores_grupo';
    protected $fillable = ['grupo_id', 'jugador_id', 'posicion', 'retirado', 'reemplaza_a_id'];

    protected $casts = [
        'retirado' => 'boolean',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(MasterGrupo::class, 'grupo_id');
    }

    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class);
    }

    /** Jugador lesionado al que reemplaza este suplente */
    public function reemplazaA(): BelongsTo
    {
        return $this->belongsTo(Jugador::class, 'reemplaza_a_id');
    }
}
