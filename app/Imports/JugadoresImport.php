<?php

namespace App\Imports;

use App\Models\Jugador;
use App\Models\Categoria;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithMappedCells;

class JugadoresImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public int $importados = 0;
    public int $omitidos   = 0;

    public function model(array $row): ?Jugador
    {
        // Buscar el valor de cada columna de forma flexible (acepta variantes)
        $apellido  = $this->valor($row, ['apellido', 'apellidos', 'last_name', 'lastname']);
        $nombre    = $this->valor($row, ['nombre', 'nombres', 'first_name', 'firstname', 'name']);
        $telefono  = $this->valor($row, ['telefono', 'teléfono', 'tel', 'phone', 'celular']);
        $ciudad    = $this->valor($row, ['ciudad', 'city', 'localidad']);
        $catNombre = $this->valor($row, ['categoria', 'categoría', 'cat', 'category']);

        // Si no hay apellido ni nombre, saltar fila
        if (empty($apellido) && empty($nombre)) {
            $this->omitidos++;
            return null;
        }

        // Buscar o ignorar categoría
        $categoriaId = null;
        if ($catNombre) {
            $cat = Categoria::whereRaw('LOWER(nombre) = ?', [strtolower(trim($catNombre))])->first();
            if ($cat) {
                $categoriaId = $cat->id;
            }
        }

        $this->importados++;

        return new Jugador([
            'apellido'     => ucwords(strtolower(trim($apellido))),
            'nombre'       => ucwords(strtolower(trim($nombre))),
            'telefono'     => preg_replace('/[^0-9]/', '', $telefono ?? '') ?: null,
            'ciudad'       => $ciudad ? ucwords(strtolower(trim($ciudad))) : null,
            'categoria_id' => $categoriaId,
        ]);
    }

    private function valor(array $row, array $claves): ?string
    {
        foreach ($claves as $clave) {
            // Buscar exacto
            if (isset($row[$clave]) && $row[$clave] !== null && $row[$clave] !== '') {
                return (string) $row[$clave];
            }
            // Buscar normalizado (sin tildes, minúsculas)
            foreach ($row as $k => $v) {
                if ($this->normalizar($k) === $this->normalizar($clave) && $v !== null && $v !== '') {
                    return (string) $v;
                }
            }
        }
        return null;
    }

    private function normalizar(string $texto): string
    {
        $texto = strtolower($texto);
        $texto = str_replace(
            ['á','é','í','ó','ú','ü','ñ',' '],
            ['a','e','i','o','u','u','n','_'],
            $texto
        );
        return trim($texto);
    }
}
