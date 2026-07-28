<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaleriaFoto extends Model
{
    protected $fillable = ['filename', 'descripcion', 'orden'];

    public function getUrlAttribute(): string
    {
        return asset('images/galeria/' . $this->filename);
    }
}
