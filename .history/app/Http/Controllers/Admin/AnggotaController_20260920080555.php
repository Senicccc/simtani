<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnggotaRequest;
use App\Http\Requests\UpdateAnggotaRequest;
use App\Models\JenisPekerjaan;
use App\Models\KompetensiAnggota;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggotaController extends Controller
{
    public function index(Request $request): View
    {
        $anggota = User::where('role', 'anggota')
            ->when($request->filled('q'), fn ($query) => $query->where(function ($member) use ($request) {
                $member->where('nama_lengkap', 'like', '%'.$request->q.'%')->orWhere('nomor_anggota', 'like', '%'.$request->q.'%');
            }))
            ->orderBy('nama_lengkap')->get();

        return view('admin.anggota.index', compact('anggota'));
    }

    public function create(): View
    {
        $jenisPekerjaan = JenisPekerjaan::where('status_aktif', true)->get();

        return view('admin.anggota.create', compact('jenisPekerjaan'));
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
            'status_aktif' => $request->boolean('status_aktif', true),
            'alamat' => $request->alamat,
        ]);

        foreach ($request->kompetensi ?? [] as $kompetensi) {
            KompetensiAnggota::create([
                'anggota_id' => $user->id,
                'jenis_pekerjaan_id' => $kompetensi['jenis_pekerjaan_id'],
                'tingkat_kemampuan' => $kompetensi['tingkat_kemampuan'],
            ]);
        }

        return redirect()->route('admin.anggota.index')->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function edit(User $anggota): View
    {
        $kompetensi = $anggota->kompetensi()->with('jenisPekerjaan')->get();
        $jenisPekerjaan = JenisPekerjaan::where('status_aktif', true)
            ->orWhereIn('id', $kompetensi->pluck('jenis_pekerjaan_id'))
            ->get();

        return view('admin.anggota.edit', compact('anggota', 'jenisPekerjaan', 'kompetensi'));
    }

    public function update(UpdateAnggotaRequest $request, User $anggota): RedirectResponse
    {
        $data = [
            'nomor_anggota' => $request->nomor_anggota,
            'nama_lengkap' => $request->nama_lengkap,
            'nomor_wa' => $request->nomor_wa,
            'email' => $request->email,
            'status_aktif' => $request->boolean('status_aktif', true),
            'alamat' => $request->alamat,
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $anggota->update($data);

        $anggota->kompetensi()->delete();
        foreach ($request->kompetensi ?? [] as $kompetensi) {
            $anggota->kompetensi()->create([
                'jenis_pekerjaan_id' => $kompetensi['jenis_pekerjaan_id'],
                'tingkat_kemampuan' => $kompetensi['tingkat_kemampuan'],
            ]);
        }

        return redirect()->route('admin.anggota.index')->with('success', 'Anggota berhasil diperbarui.');
    }
}
