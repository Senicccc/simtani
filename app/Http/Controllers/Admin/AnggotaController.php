<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnggotaRequest;
use App\Http\Requests\UpdateAnggotaRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggotaController extends Controller
{
    public function index(Request $request): View
    {
        $anggota = User::where('role', 'anggota')
            ->when($request->filled('status'), fn ($query) => $query->where('status_aktif', $request->boolean('status')))
            ->when($request->filled('q'), fn ($query) => $query->where(function ($member) use ($request) {
                $member->where('nama_lengkap', 'like', '%'.$request->q.'%')->orWhere('nomor_anggota', 'like', '%'.$request->q.'%');
            }))
            ->orderBy('nama_lengkap')->get();

        return view('admin.anggota.index', compact('anggota'));
    }

    public function create(): View
    {
        return view('admin.anggota.create');
    }

    public function store(StoreAnggotaRequest $request): RedirectResponse
    {
        $user = User::create([
            'nomor_anggota' => $request->nomor_anggota,
            'nama_lengkap' => $request->nama_lengkap,
            'foto' => $request->foto,
            'nomor_wa' => $request->nomor_wa,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'anggota',
            'status_aktif' => $request->has('status_aktif') ? $request->boolean('status_aktif') : true,
            'alamat' => $request->alamat,
        ]);

        return redirect()->route('admin.anggota.index')->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function edit(User $anggota): View
    {
        return view('admin.anggota.edit', compact('anggota'));
    }

    public function update(UpdateAnggotaRequest $request, User $anggota): RedirectResponse
    {
        $data = [
            'nama_lengkap' => $request->nama_lengkap,
            'nomor_wa' => $request->nomor_wa,
            'email' => $request->email,
            'status_aktif' => array_key_exists('status_aktif', $request->validated())
                ? $request->boolean('status_aktif')
                : $anggota->status_aktif,
            'alamat' => $request->alamat,
        ];

        $anggota->update($data);

        return redirect()->route('admin.anggota.index')->with('success', 'Anggota berhasil diperbarui.');
    }

    public function destroy(User $anggota): RedirectResponse
    {
        if ($anggota->penugasan()->exists()) {
            return redirect()->route('admin.anggota.index')
                ->withErrors(['hapus' => 'Anggota "'.$anggota->nama_lengkap.'" memiliki riwayat tugas dan tidak dapat dihapus. Nonaktifkan anggota melalui menu Edit agar riwayat tetap tersimpan.']);
        }

        $anggota->delete();

        return redirect()->route('admin.anggota.index')->with('success', 'Anggota berhasil dihapus.');
    }
}
