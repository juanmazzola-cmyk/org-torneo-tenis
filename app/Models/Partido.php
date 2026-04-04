<?php

namespace App\Models;

use App\Models\Config;
use Illuminate\Database\Eloquent\Model;

class Partido extends Model
{
    protected $fillable = [
        'draw_id', 'ronda', 'posicion',
        'jugador1_id', 'jugador2_id', 'ganador_id', 'resultado',
    ];

    public function draw()
    {
        return $this->belongsTo(Draw::class);
    }

    public function jugador1()
    {
        return $this->belongsTo(Jugador::class, 'jugador1_id');
    }

    public function jugador2()
    {
        return $this->belongsTo(Jugador::class, 'jugador2_id');
    }

    public function ganador()
    {
        return $this->belongsTo(Jugador::class, 'ganador_id');
    }

    /**
     * Retorna los puntos según la ronda donde el perdedor fue eliminado.
     * ronda = tamano/2 => primera ronda (menos puntos)
     * ronda = 1       => final (más puntos para el perdedor = 2do puesto)
     */
    public static function puntosSegunRonda(int $ronda, int $tamano): int
    {
        return match(true) {
            $ronda === 1  => (int) Config::get('puntos_subcampeon', 100),
            $ronda === 2  => (int) Config::get('puntos_semifinal',  60),
            $ronda === 4  => (int) Config::get('puntos_cuartos',    30),
            $ronda === 8  => (int) Config::get('puntos_octavos',    15),
            $ronda === 16 => (int) Config::get('puntos_16avos',     8),
            default       => (int) Config::get('puntos_32avos',     4),
        };
    }
}
