<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJadwalRequest;
use App\Http\Requests\UpdateJadwalRequest;
use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\User;
use App\Services\AutoScheduleService;
use App\Services\JadwalStatusService;
use App\Services\ScheduleConflictService;
use Carbon\CarbonPeriod;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class JadwalController extends Controller
{
    public function index(Request $request, JadwalStatusService $statuses): View
    {
        Jadwal::query()->whereNotIn('status', ['completed', 'cancelled'])->get()
            ->each(fn (Jadwal $schedule) => $statuses->refresh($schedule));
        $jadwal = Jadwal::with(['jenisPekerjaan', 'penugasan'])
            ->when(in_array($request->input('status'), ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'], true), fn ($query) => $query->where('status', $request->input('status')))
            ->orderBy('tanggal', 'desc')
            ->get();
        if ($request->boolean('needs_replacement')) {
            $jadwal = $jadwal->filter(fn (Jadwal $schedule): bool => $statuses->pendingReplacementSlots($schedule) > 0)->values();
        }

        return view('admin.jadwal.index', compact('jadwal'));
    }

    public function create(): View
    {
        $jenisPekerjaan = JenisPekerjaan::orderBy('nama_pekerjaan')->get();

        return view('admin.jadwal.create', compact('jenisPekerjaan'));
    }

    public function store(StoreJadwalRequest $request): RedirectResponse
    {
        $jadwal = Jadwal::create([
            'jenis_pekerjaan_id' => $request->jenis_pekerjaan_id,
            'nama_kegiatan' => $request->nama_kegiatan,
            'deskripsi' => $request->deskripsi,
            'lokasi' => $request->lokasi,
            'tanggal' => $request->tanggal,
            'tanggal_selesai' => $request->tanggal_selesai,
            'waktu_mulai' => $request->waktu_mulai,
            'waktu_selesai' => $request->waktu_selesai,
            'jumlah_orang_dibutuhkan' => $request->jumlah_orang_dibutuhkan,
            'jumlah_satuan' => $request->jumlah_satuan,
            'total_upah' => $request->total_upah,
            'perlu_verifikasi' => $request->boolean('perlu_verifikasi'),
            'metode_penjadwalan' => 'manual',
            'status' => 'scheduled',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.jadwal.show', $jadwal)->with('success', 'Jadwal berhasil dibuat.');
    }

    public function show(Jadwal $jadwal, AutoScheduleService $service, ScheduleConflictService $conflicts, JadwalStatusService $statuses): View
    {
        $statuses->refresh($jadwal);
        $jadwal->load(['jenisPekerjaan', 'penugasan.anggota', 'penugasan.presensiHarian', 'penugasan.presensi', 'penugasan.upah']);
        $autoSchedule = $service->recommendationsForSchedule($jadwal, $conflicts);

        return view('admin.jadwal.show', compact('jadwal', 'autoSchedule'));
    }

    public function edit(Jadwal $jadwal): View
    {
        $jenisPekerjaan = JenisPekerjaan::orderBy('nama_pekerjaan')->get();

        return view('admin.jadwal.edit', compact('jadwal', 'jenisPekerjaan'));
    }

    public function update(
        UpdateJadwalRequest $request,
        Jadwal $jadwal,
        ScheduleConflictService $conflicts,
        JadwalStatusService $statuses,
    ): RedirectResponse {
        $activeAssignments = $jadwal->penugasan()->whereIn('status', ['assigned', 'in_progress', 'waiting_verification', 'completed'])->get();

        $proposedSchedule = clone $jadwal;
        $proposedSchedule->fill($request->validated());
        foreach (CarbonPeriod::create($proposedSchedule->tanggal, $proposedSchedule->tanggal_selesai) as $date) {
            $coveredCount = $activeAssignments->filter(function ($assignment) use ($date, $proposedSchedule): bool {
                $assignmentStart = $assignment->tanggal_mulai ?? $proposedSchedule->tanggal;
                $assignmentEnd = $assignment->tanggal_selesai ?? $proposedSchedule->tanggal_selesai;

                return $assignmentStart->lte($date)
                    && $assignmentEnd->gte($date);
            })->count();

            if ($coveredCount > (int) $request->jumlah_orang_dibutuhkan) {
                return back()->withInput()->withErrors(['jumlah_orang_dibutuhkan' => 'Jumlah kebutuhan tidak boleh lebih sedikit dari penugasan yang sudah ada pada tanggal '.$date->format('d/m/Y').'.']);
            }
        }

        foreach ($activeAssignments as $assignment) {
            if (($assignment->tanggal_mulai && $assignment->tanggal_mulai->lt($proposedSchedule->tanggal))
                || ($assignment->tanggal_selesai && $assignment->tanggal_selesai->gt($proposedSchedule->tanggal_selesai))) {
                return back()->withInput()->withErrors(['tanggal_selesai' => 'Rentang jadwal tidak boleh memotong pengganti harian yang sudah ditugaskan.']);
            }

            $conflictSchedule = clone $proposedSchedule;
            $conflictSchedule->tanggal = $assignment->tanggal_mulai ?? $proposedSchedule->tanggal;
            $conflictSchedule->tanggal_selesai = $assignment->tanggal_selesai ?? $proposedSchedule->tanggal_selesai;
            if ($conflicts->hasConflict($assignment->anggota_id, $conflictSchedule, $jadwal->id)) {
                return back()->withInput()->withErrors(['tanggal' => 'Perubahan waktu akan menimbulkan bentrok untuk anggota yang sudah ditugaskan.']);
            }
        }

        if ((float) $request->total_upah !== (float) $jadwal->total_upah
            && $jadwal->penugasan()->whereHas('upah')->exists()) {
            return back()->withInput()->withErrors(['total_upah' => 'Total upah tidak dapat diubah karena sudah tercatat pada pembayaran anggota.']);
        }

        $reopensRejectedSchedule = $jadwal->status === 'completed'
            && today()->greaterThan($jadwal->tanggal_selesai ?? $jadwal->tanggal)
            && Carbon::parse($request->tanggal_selesai)->greaterThanOrEqualTo(today())
            && $jadwal->penugasan()->where('status', 'rejected')->exists();

        if ($reopensRejectedSchedule && $jadwal->penugasan()->whereHas('upah')->exists()) {
            return back()->withInput()->withErrors([
                'tanggal_selesai' => 'Tenggat tidak dapat diperpanjang karena upah sudah tercatat. Hubungi admin keuangan sebelum membuka kembali jadwal.',
            ]);
        }

        DB::transaction(function () use ($jadwal, $request, $statuses, $reopensRejectedSchedule): void {
            if ($reopensRejectedSchedule) {
                $jadwal->penugasan()->where('status', 'rejected')->get()->each(function ($assignment): void {
                    $assignment->update([
                        'status' => 'cancelled',
                        'catatan_perubahan' => trim(($assignment->catatan_perubahan ? $assignment->catatan_perubahan."\n" : '')
                            .'Laporan ditolak dan tugas lama ditutup; jadwal dibuka kembali dengan tenggat baru. Tambahkan penugasan baru untuk melanjutkan.'),
                    ]);
                });
            }

            $jadwal->update([
                'jenis_pekerjaan_id' => $request->jenis_pekerjaan_id,
                'nama_kegiatan' => $request->nama_kegiatan,
                'deskripsi' => $request->deskripsi,
                'lokasi' => $request->lokasi,
                'tanggal' => $request->tanggal,
                'tanggal_selesai' => $request->tanggal_selesai,
                'waktu_mulai' => $request->waktu_mulai,
                'waktu_selesai' => $request->waktu_selesai,
                'jumlah_orang_dibutuhkan' => $request->jumlah_orang_dibutuhkan,
                'jumlah_satuan' => $request->jumlah_satuan,
                'total_upah' => $request->total_upah,
                'perlu_verifikasi' => $request->boolean('perlu_verifikasi'),
                'metode_penjadwalan' => $request->metode_penjadwalan ?? $jadwal->metode_penjadwalan,
                'status' => $request->status === 'cancelled' ? 'cancelled' : ($reopensRejectedSchedule ? 'scheduled' : $jadwal->status),
            ]);

            $statuses->refresh($jadwal);
        });

        return redirect()->route('admin.jadwal.show', $jadwal)->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Jadwal $jadwal): RedirectResponse
    {
        if ($jadwal->penugasan()->exists()) {
            return redirect()->route('admin.jadwal.show', $jadwal)
                ->withErrors(['hapus' => 'Jadwal sudah memiliki riwayat penugasan, sehingga tidak dapat dihapus. Ubah statusnya menjadi dibatalkan.']);
        }

        try {
            $jadwal->delete();
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.jadwal.show', $jadwal)
                ->withErrors(['hapus' => 'Jadwal tidak dapat dihapus karena masih digunakan oleh data lain.']);
        }

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil dihapus.');
    }

    public function autoPreview(AutoScheduleService $service, ScheduleConflictService $conflicts, Jadwal $jadwal): JsonResponse
    {
        return response()->json($service->recommendationsForSchedule($jadwal, $conflicts));
    }

    public function generate(
        Request $request,
        AutoScheduleService $service,
        ScheduleConflictService $conflicts,
        JadwalStatusService $statuses,
        Jadwal $jadwal,
    ): RedirectResponse {
        $validated = $request->validate([
            'selected_member_ids_present' => ['nullable', 'boolean'],
            'selected_member_ids' => ['sometimes', 'array'],
            'selected_member_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);
        $selectedMemberIds = $request->boolean('selected_member_ids_present')
            ? ($validated['selected_member_ids'] ?? [])
            : null;

        try {
            $selected = $service->generateForSchedule($jadwal, $conflicts, $selectedMemberIds);
            if ($selected !== []) {
                $jadwal->update(['metode_penjadwalan' => 'otomatis']);
                $statuses->refresh($jadwal);
            }

            $message = $selected === []
                ? 'Kebutuhan anggota sudah terpenuhi. Tidak ada penugasan baru.'
                : 'Tugas diberikan kepada '.count($selected).' anggota.';

            return redirect()->route('admin.jadwal.penugasan', $jadwal)->with('success', $message);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['jadwal' => $exception->getMessage()]);
        }
    }

    public function penugasan(
        Jadwal $jadwal,
        AutoScheduleService $service,
        ScheduleConflictService $conflicts,
        JadwalStatusService $statuses,
    ): View {
        $statuses->refresh($jadwal);
        $jadwal->load(['penugasan.anggota', 'penugasan.presensiHarian', 'penugasan.presensi', 'penugasan.upah', 'jenisPekerjaan']);
        $dailyCoverage = $statuses->dailyCoverage($jadwal);
        $minimumDailyCoverage = $dailyCoverage->min('anggota') ?? 0;
        $availableSlots = max(0, $jadwal->jumlah_orang_dibutuhkan - $minimumDailyCoverage);
        $availableMembers = $availableSlots > 0
            ? $service->manualRecommendationsForSchedule($jadwal, $conflicts)
            : [];

        $activeMembers = User::query()->where('role', 'anggota')->where('status_aktif', true)->orderBy('nama_lengkap')->get();

        return view('admin.jadwal.penugasan', compact('jadwal', 'availableMembers', 'availableSlots', 'dailyCoverage', 'activeMembers'));
    }
}
