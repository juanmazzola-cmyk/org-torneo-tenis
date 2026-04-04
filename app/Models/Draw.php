<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Draw extends Model
{
    protected $fillable = ['torneo_id', 'categoria_id', 'tamano'];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function partidos()
    {
        return $this->hasMany(Partido::class);
    }

    /**
     * Genera los partidos vacíos del bracket según el tamaño del draw.
     */
    public function generarBracket(): void
    {
        $tamano = (int) $this->tamano;
        $rondas = log($tamano, 2); // 8 => 3 rondas

        $this->partidos()->delete();

        for ($ronda = $tamano / 2; $ronda >= 1; $ronda /= 2) {
            $cantPartidos = $ronda;
            for ($pos = 1; $pos <= $cantPartidos; $pos++) {
                Partido::create([
                    'draw_id'    => $this->id,
                    'ronda'      => $ronda,
                    'posicion'   => $pos,
                    'jugador1_id'=> null,
                    'jugador2_id'=> null,
                    'ganador_id' => null,
                    'resultado'  => null,
                ]);
            }
        }
    }
}
