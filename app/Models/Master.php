<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Master extends Model
{
    protected $fillable = ['torneo_id', 'categoria_id', 'estado'];

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(MasterGrupo::class)->orderBy('numero');
    }

    public function crearGrupos(): void
    {
        $this->grupos()->delete();
        $this->grupos()->createMany([
            ['numero' => 1, 'nombre' => 'Zona 1'],
            ['numero' => 2, 'nombre' => 'Zona 2'],
        ]);
    }
}
