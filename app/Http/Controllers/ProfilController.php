<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function admin(): View
    {
        return view('admin.profil', ['user' => Auth::user()]);
    }

    public function anggota(): View
    {
        return view('anggota.profil', ['user' => Auth::user()]);
    }

    public function updateAdmin(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $user->update($request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nomor_wa' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'alamat' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.profil')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updateAnggota(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $user->update($request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nomor_wa' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'alamat' => ['nullable', 'string'],
        ]));

        return redirect()->route('anggota.profil')->with('success', 'Profil berhasil diperbarui.');
    }
}
