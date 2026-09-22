<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use App\Models\PermintaanPerubahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PermintaanPerubahanController extends Controller
{
    public function index(): View
    {
        $permintaan = PermintaanPerubahan::with(['penugasan.jadwal', 'targetPenugasan.jadwal'])
            ->where('diajukan_oleh', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('anggota.permintaan-perubahan.index', compact('permintaan'));
    }

    public function create(): View
    {
        $penugasan = Penugasan::with(['jadwal.jenisPekerjaan'])
            ->where('anggota_id', Auth::id())
            ->where('status', '!=', 'completed')
            ->get();

        return view('anggota.permintaan-perubahan.create', compact('penugasan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penugasan_id' => ['required', 'exists:penugasan,id'],
            'jenis_permintaan' => ['required', 'in:tukar,sanggah'],
            'target_penugasan_id' => ['nullable', 'exists:penugasan,id'],
            'alasan' => ['required', 'string'],
        ]);

        $penugasan = Penugasan::findOrFail($validated['penugasan_id']);
        abort_unless($penugasan->anggota_id === Auth::id(), 403);

        PermintaanPerubahan::create([
            'penugasan_id' => $penugasan->id,
            'diajukan_oleh' => Auth::id(),
            'jenis_permintaan' => $validated['jenis_permintaan'],
            'target_penugasan_id' => $validated['target_penugasan_id'] ?? null,
            'alasan' => $validated['alasan'],
            'status' => 'pending',
        ]);

        return redirect()->route('anggota.permintaan-perubahan.index')->with('success', 'Permintaan perubahan berhasil dikirim.');
    }
}
