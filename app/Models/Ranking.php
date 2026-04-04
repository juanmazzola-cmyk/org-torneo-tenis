<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ranking extends Model
{
    protected $fillable = ['torneo_id', 'jugador_id', 'categoria_id', 'ronda_eliminado', 'puntos'];

    public function torneo()
    {
        return $this->belongsTo(Torneo::class);
    }

    public function jugador()
    {
        return $this->belongsTo(Jugador::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}
