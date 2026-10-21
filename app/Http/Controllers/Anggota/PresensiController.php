<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use App\Models\Presensi;
use App\Services\JadwalStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class PresensiController extends Controller
{
    public function index(): View
    {
        $presensi = Presensi::with(['penugasan.jadwal.jenisPekerjaan', 'penugasan.anggota'])
            ->whereHas('penugasan', fn ($query) => $query->where('anggota_id', Auth::id()))
            ->orderByDesc('created_at')
            ->get();

        return view('anggota.presensi.index', compact('presensi'));
    }

    public function setStatus(Request $request, Penugasan $penugasan, JadwalStatusService $statuses): RedirectResponse
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'status' => ['required', 'in:izin,tidak_hadir'],
            'catatan' => ['required_if:status,izin', 'nullable', 'string', 'max:1000'],
        ]);
        $date = Carbon::parse($validated['tanggal']);
        if ($date->lt($penugasan->tanggalMulaiEfektif()) || $date->gt($penugasan->tanggalSelesaiEfektif())) {
            return back()->withInput()->withErrors(['tanggal' => 'Pilih tanggal dalam rentang penugasan.']);
        }

        try {
            DB::transaction(function () use ($penugasan, $validated, $date, $statuses): void {
                $assignment = Penugasan::query()->lockForUpdate()->findOrFail($penugasan->id);
                if (! in_array($assignment->status, ['assigned', 'in_progress'], true)) {
                    throw new RuntimeException('Status presensi hanya dapat dicatat untuk tugas yang masih aktif.');
                }

                if ($assignment->presensiHarian()->whereDate('tanggal', $date->toDateString())->exists()) {
                    throw new RuntimeException('Presensi untuk tanggal ini sudah dicatat.');
                }

                Presensi::create([
                    'penugasan_id' => $assignment->id,
                    'tanggal' => $date->toDateString(),
                    'waktu_check_in' => null,
                    'status' => $validated['status'],
                    'catatan' => $validated['catatan'] ?? null,
                ]);
                $statuses->refresh($assignment->jadwal);
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['presensi' => $exception->getMessage()]);
        }

        return redirect()->route('anggota.tugas.show', $penugasan)->with('success', 'Presensi tanggal '.$date->format('d/m/Y').' berhasil dicatat.');
    }
}
