<?php

namespace App\Http\Controllers;

use App\Models\GaleriaFoto;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class GaleriaUploadController extends Controller
{
    private const ANCHO_MAXIMO = 1920;
    private const CALIDAD_JPG  = 85;

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'fotos'        => 'required|array|min:1',
                'fotos.*'      => 'image|mimes:jpg,jpeg,png,webp',
                'descripcion'  => 'nullable|max:300',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('galeria_error', 'Alguna imagen no es válida. Usá JPG, PNG o WebP.');
        }

        $dir = public_path('images/galeria');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $orden = (int) GaleriaFoto::max('orden');

        foreach ($request->file('fotos') as $archivo) {
            $filename = 'galeria_' . time() . '_' . Str::random(8) . '.jpg';

            $this->comprimirYGuardar($archivo, $dir . '/' . $filename);

            $orden++;
            GaleriaFoto::create([
                'filename'    => $filename,
                'descripcion' => $request->input('descripcion'),
                'orden'       => $orden,
            ]);
        }

        return redirect()->route('galeria')->with('galeria_ok', 'Fotos subidas correctamente.');
    }

    private function comprimirYGuardar(UploadedFile $archivo, string $destino): void
    {
        $ruta   = $archivo->getRealPath();
        $mime   = $archivo->getMimeType();

        $imagen = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($ruta),
            'image/png'  => imagecreatefrompng($ruta),
            'image/webp' => imagecreatefromwebp($ruta),
            default      => null,
        };

        if (!$imagen) {
            throw new \RuntimeException('No se pudo procesar la imagen.');
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $imagen = $this->corregirOrientacion($imagen, $ruta);
        }

        // Aplanar transparencia (PNG/WebP) sobre fondo blanco antes de convertir a JPG
        $ancho = imagesx($imagen);
        $alto  = imagesy($imagen);
        $plano = imagecreatetruecolor($ancho, $alto);
        imagefill($plano, 0, 0, imagecolorallocate($plano, 255, 255, 255));
        imagecopy($plano, $imagen, 0, 0, 0, 0, $ancho, $alto);
        imagedestroy($imagen);
        $imagen = $plano;

        if ($ancho > self::ANCHO_MAXIMO) {
            $nuevoAlto  = (int) round($alto * (self::ANCHO_MAXIMO / $ancho));
            $redimen    = imagecreatetruecolor(self::ANCHO_MAXIMO, $nuevoAlto);
            imagecopyresampled($redimen, $imagen, 0, 0, 0, 0, self::ANCHO_MAXIMO, $nuevoAlto, $ancho, $alto);
            imagedestroy($imagen);
            $imagen = $redimen;
        }

        imagejpeg($imagen, $destino, self::CALIDAD_JPG);
        imagedestroy($imagen);
    }

    private function corregirOrientacion(\GdImage $imagen, string $ruta): \GdImage
    {
        $exif = @exif_read_data($ruta);
        if (!$exif || empty($exif['Orientation'])) {
            return $imagen;
        }

        switch ($exif['Orientation']) {
            case 3:
                $imagen = imagerotate($imagen, 180, 0);
                break;
            case 6:
                $imagen = imagerotate($imagen, -90, 0);
                break;
            case 8:
                $imagen = imagerotate($imagen, 90, 0);
                break;
        }

        return $imagen;
    }
}
