<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = ['nombre', 'sin_ranking', 'es_dobles'];

    protected $casts = [
        'sin_ranking' => 'boolean',
        'es_dobles'   => 'boolean',
    ];

    public function jugadores()
    {
        return $this->hasMany(Jugador::class);
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }
}
