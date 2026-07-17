<?php

namespace App\Http\Controllers;

use App\Models\GaleriaFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GaleriaUploadController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'fotos'        => 'required|array|min:1',
                'fotos.*'      => 'image|mimes:jpg,jpeg,png,webp|max:4096',
                'descripcion'  => 'nullable|max:300',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('galeria_error', 'Alguna imagen no es válida. Usá JPG, PNG o WebP de hasta 4 MB.');
        }

        $dir = public_path('images/galeria');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $orden = (int) GaleriaFoto::max('orden');

        foreach ($request->file('fotos') as $archivo) {
            $ext      = strtolower($archivo->getClientOriginalExtension());
            $filename = 'galeria_' . time() . '_' . Str::random(8) . '.' . $ext;

            $archivo->move($dir, $filename);

            $orden++;
            GaleriaFoto::create([
                'filename'    => $filename,
                'descripcion' => $request->input('descripcion'),
                'orden'       => $orden,
            ]);
        }

        return redirect()->route('galeria')->with('galeria_ok', 'Fotos subidas correctamente.');
    }
}
