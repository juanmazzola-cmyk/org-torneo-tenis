<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;

class BannerUploadController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'banner' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('banner_error', 'Imagen no válida. Usá JPG, PNG o WebP de hasta 2 MB.');
        }

        $dir = public_path('images');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext      = strtolower($request->file('banner')->getClientOriginalExtension());
        $filename = 'banner-torneo.' . $ext;

        foreach (glob($dir . '/banner-torneo.*') as $old) {
            @unlink($old);
        }

        $request->file('banner')->move($dir, $filename);

        $url = asset('images/' . $filename) . '?v=' . time();
        Config::set('banner_url', $url);

        return redirect()->route('config')->with('banner_ok', 'Banner subido correctamente.');
    }
}
