<?php

namespace App\Livewire;

use App\Models\Jugador;
use App\Models\Categoria;
use App\Exports\JugadoresExport;
use App\Imports\JugadoresImport;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Jugadores extends Component
{
    use WithPagination, WithFileUploads;

    public string $buscar = '';
    public string $filtroCategoria = '';

    // Formulario
    public ?int $editandoId = null;
    public string $apellido = '';
    public string $nombre = '';
    public string $telefono = '';
    public string $ciudad = '';
    public string $categoria_id = '';

    public bool $confirmandoEliminar = false;
    public ?int $eliminarId = null;

    // Importar Excel
    public $archivoImport = null;
    public bool $mostrarImport = false;
    public string $mensajeImport = '';

    protected function rules(): array
    {
        return [
            'apellido'     => 'required|max:100',
            'nombre'       => 'required|max:100',
            'telefono'     => 'required|digits_between:10,20',
            'ciudad'       => 'required|max:100',
            'categoria_id' => 'required|exists:categorias,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'apellido.required'       => 'El apellido es obligatorio.',
            'nombre.required'         => 'El nombre es obligatorio.',
            'telefono.required'       => 'El teléfono es obligatorio.',
            'telefono.digits_between' => 'El teléfono debe tener al menos 10 dígitos.',
            'ciudad.required'         => 'La ciudad es obligatoria.',
            'categoria_id.required'   => 'La categoría es obligatoria.',
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function guardar(): void
    {
        $this->validate();
        $data = [
            'apellido'     => ucwords(strtolower(trim($this->apellido))),
            'nombre'       => ucwords(strtolower(trim($this->nombre))),
            'telefono'     => preg_replace('/[^0-9]/', '', $this->telefono),
            'ciudad'       => ucwords(strtolower(trim($this->ciudad))),
            'categoria_id' => $this->categoria_id ?: null,
        ];

        if ($this->editandoId) {
            Jugador::find($this->editandoId)->update($data);
            session()->flash('ok', 'Jugador actualizado.');
        } else {
            Jugador::create($data);
            session()->flash('ok', 'Jugador creado.');
        }
        $this->resetFormulario();
    }

    public function editar(int $id): void
    {
        $j = Jugador::findOrFail($id);
        $this->editandoId = $id;
        $this->apellido     = $j->apellido;
        $this->nombre       = $j->nombre;
        $this->telefono     = $j->telefono ?? '';
        $this->ciudad       = $j->ciudad ?? '';
        $this->categoria_id = (string) ($j->categoria_id ?? '');
    }

    public function confirmarEliminar(int $id): void
    {
        $this->eliminarId = $id;
        $this->confirmandoEliminar = true;
    }

    public function eliminar(): void
    {
        Jugador::findOrFail($this->eliminarId)->delete();
        session()->flash('ok', 'Jugador eliminado.');
        $this->reset(['confirmandoEliminar', 'eliminarId']);
    }

    public function cancelar(): void
    {
        $this->reset(['confirmandoEliminar', 'eliminarId']);
        $this->resetFormulario();
    }

    public function exportar()
    {
        return Excel::download(new JugadoresExport(), 'jugadores.xlsx');
    }

    public function importar(): void
    {
        $this->validate([
            'archivoImport' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'archivoImport.required' => 'Seleccioná un archivo.',
            'archivoImport.mimes'    => 'El archivo debe ser .xlsx, .xls o .csv.',
            'archivoImport.max'      => 'El archivo no puede superar 5MB.',
        ]);

        $import = new JugadoresImport();
        Excel::import($import, $this->archivoImport->getRealPath());

        $this->mensajeImport = "✅ Importación completada: {$import->importados} jugadores importados, {$import->omitidos} filas omitidas.";
        $this->archivoImport = null;
    }

    public function crearBye(): void
    {
        if (Jugador::where('apellido', 'Bye')->where('nombre', 'Bye')->exists()) {
            session()->flash('ok', 'El jugador Bye ya existe.');
            return;
        }

        Jugador::create([
            'apellido'     => 'Bye',
            'nombre'       => 'Bye',
            'telefono'     => '0000000000',
            'ciudad'       => 'N/A',
            'categoria_id' => null,
        ]);

        session()->flash('ok', 'Jugador Bye creado. Ya podés asignarlo en el draw.');
    }

    private function resetFormulario(): void
    {
        $this->reset(['editandoId', 'apellido', 'nombre', 'telefono', 'ciudad', 'categoria_id']);
    }

    public function render()
    {
        $byeExiste = Jugador::where('apellido', 'Bye')->where('nombre', 'Bye')->exists();

        $jugadores = Jugador::with('categoria')
            ->when($this->buscar, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('apellido', 'like', '%' . $this->buscar . '%')
                       ->orWhere('nombre', 'like', '%' . $this->buscar . '%')
                       ->orWhere('ciudad', 'like', '%' . $this->buscar . '%')
                )
            )
            ->when($this->filtroCategoria, fn($q) =>
                $q->where('categoria_id', $this->filtroCategoria)
            )
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(20);

        return view('livewire.jugadores', [
            'jugadores'  => $jugadores,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'byeExiste'  => $byeExiste,
        ])->layout('layouts.app');
    }
}
