<?php

namespace App\Exports;

use App\Models\Jugador;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JugadoresExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function query()
    {
        return Jugador::with('categoria')->orderBy('apellido')->orderBy('nombre');
    }

    public function headings(): array
    {
        return ['Apellido', 'Nombre', 'Teléfono', 'Ciudad', 'Categoría'];
    }

    public function map($jugador): array
    {
        return [
            $jugador->apellido,
            $jugador->nombre,
            $jugador->telefono,
            $jugador->ciudad,
            $jugador->categoria?->nombre ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
