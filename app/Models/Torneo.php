<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Torneo extends Model
{
    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'estado'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function draws()
    {
        return $this->hasMany(Draw::class);
    }

    public function rankings()
    {
        return $this->hasMany(Ranking::class);
    }
}
