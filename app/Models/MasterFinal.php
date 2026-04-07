<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterFinal extends Model
{
    protected $table = 'master_finales';

    protected $fillable = [
        'master_id', 'tipo',
        'jugador1_id', 'jugador2_id',
        'resultado', 'ganador_id',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
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

    public function label(): string
    {
        return match($this->tipo) {
            'semifinal_1' => 'Semifinal 1',
            'semifinal_2' => 'Semifinal 2',
            'final'       => 'Final',
        };
    }

    public function perdedor_id(): ?int
    {
        if (!$this->ganador_id) return null;
        return $this->ganador_id === $this->jugador1_id
            ? $this->jugador2_id
            : $this->jugador1_id;
    }
}
