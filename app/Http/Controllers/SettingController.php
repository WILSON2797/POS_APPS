<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    private function getSettings()
    {
        $path = storage_path('app/settings.json');
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?: [];
        }
        return [];
    }

    private function saveSettings(array $data)
    {
        $path = storage_path('app/settings.json');
        $settings = $this->getSettings();
        $newSettings = array_merge($settings, $data);
        file_put_contents($path, json_encode($newSettings, JSON_PRETTY_PRINT));
    }

    public function logo()
    {
        $settings = $this->getSettings();
        return Inertia::render('Settings/Logo', [
            'brand_name' => $settings['brand_name'] ?? config('app.name', 'POS Apps'),
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo_full' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo_mini' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'brand_name' => 'nullable|string|max:50',
        ]);

        if ($request->has('brand_name')) {
            $this->saveSettings(['brand_name' => $request->input('brand_name')]);
        }

        $uploadPath = public_path('uploads');
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        if ($request->hasFile('logo_full')) {
            $file = $request->file('logo_full');
            if (File::exists(public_path('uploads/logo_full.png'))) {
                File::delete(public_path('uploads/logo_full.png'));
            }
            $file->move($uploadPath, 'logo_full.png');
        }

        if ($request->hasFile('logo_mini')) {
            $file = $request->file('logo_mini');
            if (File::exists(public_path('uploads/logo_mini.png'))) {
                File::delete(public_path('uploads/logo_mini.png'));
            }
            $file->move($uploadPath, 'logo_mini.png');
        }

        return back()->with('success', 'Logo & Nama Brand berhasil diperbarui.');
    }

    public function resetLogo(Request $request)
    {
        $type = $request->input('type', 'all');

        if ($type === 'brand') {
            $this->saveSettings(['brand_name' => config('app.name', 'POS Apps')]);
        }

        if ($type === 'full' || $type === 'all') {
            if (File::exists(public_path('uploads/logo_full.png'))) {
                File::delete(public_path('uploads/logo_full.png'));
            }
        }

        if ($type === 'mini' || $type === 'all') {
            if (File::exists(public_path('uploads/logo_mini.png'))) {
                File::delete(public_path('uploads/logo_mini.png'));
            }
        }

        return back()->with('success', 'Logo & Nama Brand berhasil dikembalikan ke default.');
    }
}
