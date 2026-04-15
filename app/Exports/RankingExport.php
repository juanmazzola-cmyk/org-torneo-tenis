<?php

namespace App\Exports;

use App\Models\Ranking;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RankingExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        private ?int $torneoId    = null,
        private ?int $categoriaId = null,
        private ?int $anio        = null,
    ) {}

    public function query()
    {
        $query = Ranking::with(['jugador', 'categoria', 'torneo'])
            ->orderBy('categoria_id')
            ->orderByDesc('puntos');

        if ($this->torneoId) {
            $query->where('torneo_id', $this->torneoId);
        }
        if ($this->categoriaId) {
            $query->where('categoria_id', $this->categoriaId);
        }
        if ($this->anio) {
            $query->whereHas('torneo', fn($q) => $q->whereYear('fecha_inicio', $this->anio));
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Torneo', 'Categoría', 'Apellido', 'Nombre', 'Ronda Eliminado', 'Puntos'];
    }

    public function map($ranking): array
    {
        $rondaNombre = match($ranking->ronda_eliminado) {
            1  => 'Final',
            2  => 'Semifinal',
            4  => 'Cuartos de Final',
            8  => 'Octavos',
            16 => '16avos',
            default => 'Ronda ' . $ranking->ronda_eliminado,
        };

        return [
            $ranking->torneo->nombre,
            $ranking->categoria->nombre,
            $ranking->jugador->apellido,
            $ranking->jugador->nombre,
            $rondaNombre,
            $ranking->puntos,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
