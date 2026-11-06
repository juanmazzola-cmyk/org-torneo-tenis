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

    /**
     * Dobles con 8 clasificados: a qué semi y a qué lugar pasa el ganador de cada cuarto.
     * Semi 1 = ganador CF1 vs ganador CF2; Semi 2 = ganador CF3 vs ganador CF4.
     */
    public const CUARTOS_A_SEMI = [
        'cuartos_1' => ['semifinal_1', 'jugador1_id'],
        'cuartos_2' => ['semifinal_1', 'jugador2_id'],
        'cuartos_3' => ['semifinal_2', 'jugador1_id'],
        'cuartos_4' => ['semifinal_2', 'jugador2_id'],
    ];

    /** Orden de los partidos para FIELD(tipo, ...) */
    public const ORDEN = "'cuartos_1', 'cuartos_2', 'cuartos_3', 'cuartos_4', 'semifinal_1', 'semifinal_2', 'final'";

    public function esCuartos(): bool
    {
        return str_starts_with($this->tipo, 'cuartos_');
    }

    public function label(): string
    {
        return match($this->tipo) {
            'cuartos_1'   => 'Cuartos 1',
            'cuartos_2'   => 'Cuartos 2',
            'cuartos_3'   => 'Cuartos 3',
            'cuartos_4'   => 'Cuartos 4',
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
