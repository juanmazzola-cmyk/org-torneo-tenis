<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jugador extends Model
{
    protected $table = 'jugadores';
    protected $fillable = ['apellido', 'nombre', 'telefono', 'ciudad', 'categoria_id'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->apellido . ', ' . $this->nombre;
    }

    public function getWhatsappLinkAttribute(): string
    {
        $tel = preg_replace('/[^0-9]/', '', $this->telefono ?? '');
        return 'https://wa.me/' . $tel;
    }
}
