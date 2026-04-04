<?php

namespace App\Exports;

use App\Models\Inscripcion;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InscripcionesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private int $torneoId, private string $torneoNombre) {}

    public function query()
    {
        return Inscripcion::with(['jugador', 'categoria'])
            ->join('jugadores', 'jugadores.id', '=', 'inscripciones.jugador_id')
            ->where('torneo_id', $this->torneoId)
            ->orderBy('inscripciones.categoria_id')
            ->orderBy('jugadores.apellido')
            ->select('inscripciones.*');
    }

    public function title(): string
    {
        return substr($this->torneoNombre, 0, 31);
    }

    public function headings(): array
    {
        return ['Apellido', 'Nombre', 'Teléfono', 'Categoría', 'Observación'];
    }

    public function map($inscripcion): array
    {
        return [
            $inscripcion->jugador->apellido,
            $inscripcion->jugador->nombre,
            $inscripcion->jugador->telefono,
            $inscripcion->categoria->nombre,
            $inscripcion->observacion,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
