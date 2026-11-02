<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnggotaRequest;
use App\Http\Requests\UpdateAnggotaRequest;
use App\Models\JenisPekerjaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnggotaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['0', '1'])],
        ]);
        $search = trim($filters['q'] ?? '');

        $anggota = User::query()
            ->where('role', 'anggota')
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status_aktif', $request->boolean('status')))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $member) use ($search): void {
                $member->where('nama_lengkap', 'like', '%'.$search.'%')
                    ->orWhere('nomor_anggota', 'like', '%'.$search.'%');
            }))
            ->orderBy('nama_lengkap')
            ->select(['id', 'nomor_anggota', 'nama_lengkap', 'email', 'status_aktif'])
            ->paginate(15)
            ->withQueryString();

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
        abort_unless($anggota->role === 'anggota', 404);

        $jenisPekerjaan = JenisPekerjaan::query()->orderBy('nama_pekerjaan')->get(['id', 'nama_pekerjaan']);
        $kompetensi = $anggota->kompetensi()->pluck('tingkat_kemampuan', 'jenis_pekerjaan_id');

        return view('admin.anggota.edit', compact('anggota', 'jenisPekerjaan', 'kompetensi'));
    }

    public function update(UpdateAnggotaRequest $request, User $anggota): RedirectResponse
    {
        abort_unless($anggota->role === 'anggota', 404);

        $data = [
            'nama_lengkap' => $request->nama_lengkap,
            'nomor_wa' => $request->nomor_wa,
            'email' => $request->email,
            'status_aktif' => array_key_exists('status_aktif', $request->validated())
                ? $request->boolean('status_aktif')
                : $anggota->status_aktif,
            'alamat' => $request->alamat,
        ];

        $competencies = $request->validated('kompetensi');

        DB::transaction(function () use ($anggota, $data, $competencies): void {
            $anggota->update($data);

            foreach ($competencies ?? [] as $competency) {
                if ($competency['tingkat_kemampuan'] === null) {
                    $anggota->kompetensi()
                        ->where('jenis_pekerjaan_id', $competency['jenis_pekerjaan_id'])
                        ->delete();

                    continue;
                }

                $anggota->kompetensi()->updateOrCreate(
                    ['jenis_pekerjaan_id' => $competency['jenis_pekerjaan_id']],
                    ['tingkat_kemampuan' => $competency['tingkat_kemampuan']],
                );
            }
        });

        return redirect()->route('admin.anggota.index')->with('success', 'Anggota berhasil diperbarui.');
    }

    public function destroy(User $anggota): RedirectResponse
    {
        abort_unless($anggota->role === 'anggota', 404);

        if ($anggota->penugasan()->exists() || $anggota->penugasanOriginal()->exists()) {
            return redirect()->route('admin.anggota.index')
                ->withErrors(['hapus' => 'Anggota "'.$anggota->nama_lengkap.'" memiliki riwayat tugas dan tidak dapat dihapus. Nonaktifkan anggota melalui menu Edit agar riwayat tetap tersimpan.']);
        }

        $anggota->delete();

        return redirect()->route('admin.anggota.index')->with('success', 'Anggota berhasil dihapus.');
    }
}
