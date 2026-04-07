<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterPartido extends Model
{
    protected $fillable = [
        'grupo_id', 'jornada', 'orden',
        'jugador1_id', 'jugador2_id',
        'resultado', 'ganador_id',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(MasterGrupo::class, 'grupo_id');
    }

    public function jugador1(): BelongsTo
    {
        return $this->belongsTo(Jugador::class, 'jugador1_id');
    }

    public function jugador2(): BelongsTo
    {
        return $this->belongsTo(Jugador::class, 'jugador2_id');
    }

    public function ganador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class, 'ganador_id');
    }
}
