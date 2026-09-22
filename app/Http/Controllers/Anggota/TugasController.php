<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TugasController extends Controller
{
    public function index(Request $request): View
    {
        $penugasan = Penugasan::with(['jadwal.jenisPekerjaan'])
            ->where('anggota_id', Auth::id())
            ->when($request->filled('q'), fn ($query) => $query->whereHas('jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$request->q.'%')))
            ->orderByDesc('assigned_at')
            ->get();

        return view('anggota.tugas.index', compact('penugasan'));
    }

    public function show(Penugasan $penugasan): View
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);
        $penugasan->load(['jadwal.jenisPekerjaan', 'presensi', 'upah']);
        $rekanTugas = Penugasan::with('anggota')
            ->where('jadwal_id', $penugasan->jadwal_id)
            ->where('id', '!=', $penugasan->id)
            ->where('status', '!=', 'cancelled')
            ->get();

        return view('anggota.tugas.show', compact('penugasan', 'rekanTugas'));
    }

    public function mulai(Penugasan $penugasan): RedirectResponse
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);
        $penugasan->update(['status' => 'in_progress', 'started_at' => now()]);

        return redirect()->route('anggota.tugas.show', $penugasan)->with('success', 'Tugas dimulai.');
    }

    public function showComplete(Penugasan $penugasan): View
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);

        return view('anggota.tugas.complete', compact('penugasan'));
    }

    public function complete(Request $request, Penugasan $penugasan): RedirectResponse
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);
        $validated = $request->validate([
            'catatan_penyelesaian' => ['nullable', 'string'],
            'bukti_selesai' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $path = null;
        if ($request->hasFile('bukti_selesai')) {
            $path = $request->file('bukti_selesai')->store('bukti-selesai', 'public');
        }

        $penugasan->update([
            'status' => 'waiting_verification',
            'completed_at' => now(),
            'catatan_penyelesaian' => $validated['catatan_penyelesaian'] ?? null,
            'bukti_selesai' => $path,
        ]);

        return redirect()->route('anggota.tugas.index')->with('success', 'Tugas dikirim untuk verifikasi admin.');
    }

    public function riwayat(): View
    {
        $penugasan = Penugasan::with(['jadwal.jenisPekerjaan'])
            ->where('anggota_id', Auth::id())
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->get();

        return view('anggota.riwayat-tugas', compact('penugasan'));
    }

    public function contactAdmin(): View
    {
        $admins = User::where('role', 'admin')->where('status_aktif', true)->orderBy('nama_lengkap')->get();

        return view('anggota.hubungi-admin', compact('admins'));
    }
}
