<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $fillable = ['clave', 'valor', 'descripcion'];

    public static function get(string $clave, mixed $default = null): mixed
    {
        $config = static::where('clave', $clave)->first();
        return $config ? $config->valor : $default;
    }

    public static function set(string $clave, mixed $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }
}
