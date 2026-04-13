<?php

namespace App\Livewire;

use App\Models\Config;
use Livewire\Component;

class Configuracion extends Component
{
    public string $club_nombre = '';
    public string $club_ciudad = '';
    public string $club_telefono = '';
    public string $puntos_campeon   = '';
    public string $puntos_subcampeon = '';
    public string $puntos_semifinal = '';
    public string $puntos_cuartos  = '';
    public string $puntos_octavos  = '';
    public string $puntos_16avos   = '';
    public string $puntos_32avos   = '';
    public string $admin_code      = '';
    public string $panel_info     = '';

    public function mount(): void
    {
        $this->club_nombre       = Config::get('club_nombre', '');
        $this->club_ciudad       = Config::get('club_ciudad', '');
        $this->club_telefono     = Config::get('club_telefono', '');
        $this->puntos_campeon    = Config::get('puntos_campeon',    '150');
        $this->puntos_subcampeon = Config::get('puntos_subcampeon', '100');
        $this->puntos_semifinal  = Config::get('puntos_semifinal',  '60');
        $this->puntos_cuartos    = Config::get('puntos_cuartos',    '30');
        $this->puntos_octavos    = Config::get('puntos_octavos',    '15');
        $this->puntos_16avos     = Config::get('puntos_16avos',     '8');
        $this->puntos_32avos     = Config::get('puntos_32avos',     '4');
        $this->admin_code        = Config::get('admin_code',        'ADMIN1');
        $this->panel_info        = Config::get('panel_info',        '');
    }

    protected function rules(): array
    {
        return [
            'club_nombre'      => 'nullable|max:200',
            'club_ciudad'      => 'nullable|max:100',
            'club_telefono'    => 'nullable|max:30',
            'puntos_campeon'    => 'required|integer|min:0',
            'puntos_subcampeon' => 'required|integer|min:0',
            'puntos_semifinal'  => 'required|integer|min:0',
            'puntos_cuartos'    => 'required|integer|min:0',
            'puntos_octavos'    => 'required|integer|min:0',
            'puntos_16avos'     => 'required|integer|min:0',
            'puntos_32avos'     => 'required|integer|min:0',
            'admin_code'        => 'required|min:4|max:20',
            'panel_info'        => 'nullable|max:2000',
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        Config::set('club_nombre',       $this->club_nombre);
        Config::set('club_ciudad',       $this->club_ciudad);
        Config::set('club_telefono',     $this->club_telefono);
        Config::set('puntos_campeon',    $this->puntos_campeon);
        Config::set('puntos_subcampeon', $this->puntos_subcampeon);
        Config::set('puntos_semifinal',  $this->puntos_semifinal);
        Config::set('puntos_cuartos',    $this->puntos_cuartos);
        Config::set('puntos_octavos',    $this->puntos_octavos);
        Config::set('puntos_16avos',     $this->puntos_16avos);
        Config::set('puntos_32avos',     $this->puntos_32avos);
        Config::set('admin_code',        $this->admin_code);
        Config::set('panel_info',        $this->panel_info);

        session()->flash('ok', 'Configuración guardada correctamente.');
    }

    public function render()
    {
        return view('livewire.configuracion')
            ->layout('layouts.app');
    }
}
