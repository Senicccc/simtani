<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use App\Models\Presensi;
use App\Models\User;
use App\Services\JadwalStatusService;
use App\Services\UpahTugasService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class TugasController extends Controller
{
    public function index(Request $request): View
    {
        $penugasan = Penugasan::with(['jadwal.jenisPekerjaan', 'presensiHarian', 'presensi', 'upah'])
            ->where('anggota_id', Auth::id())
            ->when($request->filled('q'), fn ($query) => $query->whereHas('jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$request->q.'%')))
            ->orderByDesc('assigned_at')
            ->get();

        return view('anggota.tugas.index', compact('penugasan'));
    }

    public function show(Penugasan $penugasan): View
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);
        $penugasan->load(['jadwal.jenisPekerjaan', 'presensiHarian', 'presensi', 'upah']);
        $rekanTugas = Penugasan::with('anggota')
            ->where('jadwal_id', $penugasan->jadwal_id)
            ->where('id', '!=', $penugasan->id)
            ->where('status', '!=', 'cancelled')
            ->get();

        return view('anggota.tugas.show', compact('penugasan', 'rekanTugas'));
    }

    public function mulai(Request $request, Penugasan $penugasan, JadwalStatusService $statuses): RedirectResponse
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);
        if (! in_array($penugasan->status, ['assigned', 'in_progress'], true)) {
            return back()->withErrors(['status' => 'Tugas ini tidak dapat dimulai. Periksa kembali status dan jadwalnya.']);
        }

        $date = today();
        if ($date->lt($penugasan->tanggalMulaiEfektif()) || $date->gt($penugasan->tanggalSelesaiEfektif())) {
            return back()->withErrors(['status' => 'Kehadiran hanya dapat dicatat pada tanggal penugasan.']);
        }

        try {
            DB::transaction(function () use ($penugasan, $date, $statuses): void {
                $penugasan = Penugasan::query()->lockForUpdate()->findOrFail($penugasan->id);
                if (! in_array($penugasan->status, ['assigned', 'in_progress'], true)) {
                    throw new RuntimeException('Tugas ini tidak lagi dapat dimulai.');
                }

                $presensi = $penugasan->presensiHarian()->whereDate('tanggal', $date->toDateString())->first();
                if ($presensi && $presensi->status !== 'hadir') {
                    throw new RuntimeException('Presensi hari ini sudah tercatat sebagai izin atau tidak hadir.');
                }

                $penugasan->update([
                    'status' => 'in_progress',
                    'started_at' => $penugasan->started_at ?? now(),
                ]);
                if ($presensi) {
                    $presensi->update(['waktu_check_in' => $presensi->waktu_check_in ?? now()]);
                } else {
                    Presensi::create([
                        'penugasan_id' => $penugasan->id,
                        'tanggal' => $date->toDateString(),
                        'waktu_check_in' => now(),
                        'status' => 'hadir',
                    ]);
                }
                $statuses->refresh($penugasan->jadwal);
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return redirect()->route('anggota.tugas.show', $penugasan)->with('success', 'Tugas dimulai.');
    }

    public function checkOut(Penugasan $penugasan): RedirectResponse
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);

        $date = today();
        if ($date->lt($penugasan->tanggalMulaiEfektif()) || $date->gt($penugasan->tanggalSelesaiEfektif())) {
            return back()->withErrors(['presensi' => 'Check-out hanya dapat dicatat pada tanggal penugasan.']);
        }

        try {
            DB::transaction(function () use ($penugasan, $date): void {
                $assignment = Penugasan::query()->lockForUpdate()->findOrFail($penugasan->id);
                if (! in_array($assignment->status, ['assigned', 'in_progress'], true)) {
                    throw new RuntimeException('Check-out hanya tersedia untuk tugas yang masih aktif.');
                }

                $presence = $assignment->presensiHarian()
                    ->whereDate('tanggal', $date->toDateString())
                    ->lockForUpdate()
                    ->first();

                if (! $presence || $presence->status !== 'hadir' || ! $presence->waktu_check_in) {
                    throw new RuntimeException('Catat check-in hadir terlebih dahulu sebelum check-out.');
                }

                if ($presence->waktu_check_out) {
                    throw new RuntimeException('Check-out hari ini sudah dicatat.');
                }

                $presence->update(['waktu_check_out' => now()]);
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors(['presensi' => $exception->getMessage()]);
        }

        return redirect()->route('anggota.tugas.show', $penugasan)->with('success', 'Check-out hari ini berhasil dicatat.');
    }

    public function showComplete(Penugasan $penugasan): View
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);
        abort_unless($penugasan->status === 'in_progress', 404);
        $penugasan->loadMissing('jadwal');

        return view('anggota.tugas.complete', compact('penugasan'));
    }

    public function complete(Request $request, Penugasan $penugasan, UpahTugasService $upahService, JadwalStatusService $statuses): RedirectResponse
    {
        abort_unless($penugasan->anggota_id === Auth::id(), 403);
        if ($penugasan->status !== 'in_progress') {
            return back()->withErrors(['status' => 'Mulai tugas terlebih dahulu sebelum melaporkan pekerjaan selesai.']);
        }

        $validated = $request->validate([
            'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'catatan_penyelesaian' => ['nullable', 'string'],
            'bukti_selesai' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $completedDate = Carbon::parse($validated['tanggal_selesai']);
        if ($completedDate->lt($penugasan->tanggalMulaiEfektif()) || $completedDate->gt($penugasan->tanggalSelesaiEfektif())) {
            return back()->withInput()->withErrors(['tanggal_selesai' => 'Tanggal penyelesaian harus berada dalam rentang penugasan.']);
        }

        $path = null;
        if ($request->hasFile('bukti_selesai')) {
            $path = $request->file('bukti_selesai')->store('bukti-selesai', 'public');
        }

        try {
            DB::transaction(function () use ($penugasan, $validated, $path, $upahService, $statuses, $completedDate): void {
                $penugasan = Penugasan::query()->lockForUpdate()->findOrFail($penugasan->id);
                if ($penugasan->status !== 'in_progress') {
                    throw new RuntimeException('Tugas sudah dilaporkan selesai atau statusnya telah berubah. Muat ulang halaman untuk melihat status terbaru.');
                }

                $presence = $penugasan->presensiHarian()->whereDate('tanggal', $completedDate->toDateString())->first();
                if (! $presence || $presence->status !== 'hadir' || ! $presence->waktu_check_in) {
                    throw new RuntimeException('Catat kehadiran pada tanggal penyelesaian sebelum mengirim laporan selesai.');
                }

                $workedDays = $penugasan->presensiHarian()
                    ->where('status', 'hadir')
                    ->whereDate('tanggal', '<=', $completedDate->toDateString())
                    ->exists();
                if (! $workedDays) {
                    throw new RuntimeException('Izin atau tidak hadir tidak dapat dilaporkan sebagai pekerjaan selesai.');
                }

                $completedAt = Carbon::parse($validated['tanggal_selesai'])->setTime(
                    now()->hour,
                    now()->minute,
                    now()->second,
                );
                $penugasan->update([
                    'status' => $penugasan->jadwal->perlu_verifikasi ? 'waiting_verification' : 'completed',
                    'completed_at' => $completedAt,
                    'verified_at' => $penugasan->jadwal->perlu_verifikasi ? null : $completedAt,
                    'catatan_verifikasi' => $penugasan->jadwal->perlu_verifikasi ? null : 'Selesai otomatis sesuai pengaturan tugas.',
                    'catatan_penyelesaian' => $validated['catatan_penyelesaian'] ?? null,
                    'bukti_selesai' => $path,
                ]);
                $presence->update(['waktu_check_out' => now()]);

                if (! $penugasan->jadwal->perlu_verifikasi) {
                    $upahService->distributeForCompletedSchedule($penugasan->jadwal);
                }
                $statuses->refresh($penugasan->jadwal);
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['status' => $exception->getMessage()]);
        }

        $message = $penugasan->jadwal->perlu_verifikasi
            ? 'Laporan berhasil dikirim. Upah dibagi rata setelah admin menyetujui seluruh laporan kegiatan.'
            : 'Tugas selesai. Bagian upah tersedia setelah seluruh anggota menyelesaikan tugas.';

        return redirect()->route('anggota.tugas.index')->with('success', $message);
    }

    public function riwayat(): View
    {
        $penugasan = Penugasan::with(['jadwal.jenisPekerjaan', 'presensiHarian', 'presensi', 'upah'])
            ->where('anggota_id', Auth::id())
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->get();

        return view('anggota.tugas.riwayat', compact('penugasan'));
    }

    public function contactAdmin(): View
    {
        $admins = User::where('role', 'admin')->where('status_aktif', true)->orderBy('nama_lengkap')->get();

        return view('anggota.hubungi-admin', compact('admins'));
    }
}
