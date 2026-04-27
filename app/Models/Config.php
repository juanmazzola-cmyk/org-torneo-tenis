<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Config extends Model
{
    protected $fillable = ['clave', 'valor', 'descripcion'];

    public static function get(string $clave, mixed $default = null): mixed
    {
        $all = Cache::remember('app_config', 3600, fn() =>
            static::all()->pluck('valor', 'clave')->toArray()
        );
        return $all[$clave] ?? $default;
    }

    public static function set(string $clave, mixed $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        Cache::forget('app_config');
    }
}
