<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = ['nombre'];

    public function jugadores()
    {
        return $this->hasMany(Jugador::class);
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }
}
