<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use App\Models\Presensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PresensiController extends Controller
{
    public function index(): View
    {
        $penugasan = Penugasan::with(['jadwal', 'presensi'])
            ->where('anggota_id', Auth::id())
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('assigned_at')->get();
        $presensi = Presensi::with(['penugasan.jadwal.jenisPekerjaan', 'penugasan.anggota'])
            ->whereHas('penugasan', fn ($query) => $query->where('anggota_id', Auth::id()))
            ->orderByDesc('created_at')
            ->get();

        return view('anggota.presensi.index', compact('presensi', 'penugasan'));
    }

    public function checkIn(Penugasan $penugasan): RedirectResponse
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);

        if ($penugasan->presensi()->exists()) {
            return back()->withErrors(['presensi' => 'Presensi sudah dibuat untuk tugas ini.']);
        }

        Presensi::create([
            'penugasan_id' => $penugasan->id,
            'waktu_check_in' => now(),
            'status' => 'hadir',
        ]);

        return redirect()->route('anggota.presensi.index')->with('success', 'Check-in berhasil dicatat.');
    }

    public function checkOut(Penugasan $penugasan): RedirectResponse
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);

        $presensi = $penugasan->presensi;
        if (! $presensi) {
            return back()->withErrors(['presensi' => 'Belum ada check-in untuk tugas ini.']);
        }

        if ($presensi->waktu_check_out) {
            return back()->withErrors(['presensi' => 'Check-out sudah dicatat.']);
        }

        $presensi->update([
            'waktu_check_out' => now(),
            'status' => 'hadir',
        ]);

        return redirect()->route('anggota.presensi.index')->with('success', 'Check-out berhasil dicatat.');
    }
}
