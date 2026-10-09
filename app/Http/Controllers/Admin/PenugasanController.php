<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\User;
use App\Services\JadwalStatusService;
use App\Services\ScheduleConflictService;
use App\Services\UpahTugasService;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class PenugasanController extends Controller
{
    public function index(Request $request, JadwalStatusService $statuses): View
    {
        $jadwal = Jadwal::with(['jenisPekerjaan', 'penugasan.anggota'])
            ->when($request->filled('q'), fn ($query) => $query->where(function ($matches) use ($request): void {
                $matches->where('nama_kegiatan', 'like', '%'.$request->q.'%')
                    ->orWhereHas('penugasan.anggota', fn ($member) => $member->where('nama_lengkap', 'like', '%'.$request->q.'%'));
            }))
            ->when($request->input('status') === 'active', fn ($query) => $query->whereHas('penugasan', fn ($assignment) => $assignment->whereIn('status', ['assigned', 'in_progress', 'waiting_verification'])))
            ->when(in_array($request->input('status'), ['assigned', 'in_progress', 'waiting_verification', 'completed', 'cancelled'], true), fn ($query) => $query->whereHas('penugasan', fn ($assignment) => $assignment->where('status', $request->input('status'))))
            ->orderByDesc('tanggal')->get();
        $jadwal->each(function (Jadwal $schedule) use ($statuses): void {
            $schedule->setAttribute('anggota_terisi', $statuses->dailyCoverage($schedule)->min('anggota') ?? 0);
        });

        return view('admin.penugasan.index', compact('jadwal'));
    }

    public function create(): View
    {
        $jadwal = Jadwal::with('jenisPekerjaan')->whereIn('status', ['draft', 'scheduled', 'ongoing'])->orderBy('tanggal')->get();
        $anggota = User::where('role', 'anggota')->where('status_aktif', true)->orderBy('nama_lengkap')->get();

        return view('admin.penugasan.create', compact('jadwal', 'anggota'));
    }

    public function store(Request $request, ScheduleConflictService $conflicts, JadwalStatusService $statuses): RedirectResponse
    {
        $validated = $request->validate([
            'jadwal_id' => ['required', 'exists:jadwal,id'],
            'anggota_id' => ['required', 'exists:users,id'],
        ]);
        $schedule = Jadwal::findOrFail($validated['jadwal_id']);
        $member = User::where('role', 'anggota')->where('status_aktif', true)->findOrFail($validated['anggota_id']);

        if (! in_array($schedule->status, ['draft', 'scheduled', 'ongoing'], true)) {
            return back()->withInput()->withErrors(['jadwal_id' => 'Penugasan hanya dapat ditambahkan pada jadwal draft atau terjadwal.']);
        }

        $minimumDailyCoverage = $statuses->dailyCoverage($schedule)->min('anggota') ?? 0;
        if ($minimumDailyCoverage >= $schedule->jumlah_orang_dibutuhkan) {
            return back()->withInput()->withErrors(['jadwal_id' => 'Kebutuhan anggota untuk jadwal ini sudah terpenuhi.']);
        }

        if (Penugasan::where('jadwal_id', $schedule->id)->where('anggota_id', $member->id)->where('status', '!=', 'cancelled')->exists()) {
            return back()->withInput()->withErrors(['anggota_id' => 'Anggota sudah ditugaskan pada kegiatan ini.']);
        }

        if ($conflicts->hasConflict($member->id, $schedule)) {
            return back()->withInput()->withErrors(['anggota_id' => 'Anggota memiliki bentrok jadwal pada waktu tersebut.']);
        }

        $penugasan = Penugasan::create([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $member->id,
            'original_anggota_id' => $member->id,
            'status' => 'assigned',
            'sumber_penugasan' => 'manual',
            'assigned_at' => now(),
        ]);
        $statuses->refresh($schedule);

        return redirect()->route('admin.penugasan.show', $penugasan)->with('success', 'Penugasan berhasil ditambahkan.');
    }

    public function replaceForDate(Request $request, Jadwal $jadwal, ScheduleConflictService $conflicts, JadwalStatusService $statuses): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:'.$jadwal->tanggal->toDateString(), 'before_or_equal:'.($jadwal->tanggal_selesai ?? $jadwal->tanggal)->toDateString()],
            'anggota_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $member = User::query()->where('role', 'anggota')->where('status_aktif', true)->findOrFail($validated['anggota_id']);
        $date = Carbon::parse($validated['tanggal']);
        $existingAssignments = $jadwal->penugasan()
            ->with('presensiHarian')
            ->where('status', '!=', 'cancelled')
            ->get()
            ->filter(fn (Penugasan $assignment): bool => $assignment->tanggalMulaiEfektif()->lte($date)
                && $assignment->tanggalSelesaiEfektif()->gte($date));
        $absentCount = $existingAssignments->filter(fn (Penugasan $assignment): bool => in_array(
            $assignment->presensiHarian->first(fn ($presence): bool => $presence->tanggal->isSameDay($date))?->status,
            ['izin', 'tidak_hadir'],
            true,
        ))->count();
        $coveredCount = $existingAssignments->count() - $absentCount;

        if ($absentCount === 0 || $coveredCount >= $jadwal->jumlah_orang_dibutuhkan) {
            return back()->withErrors(['tanggal' => 'Tidak ada kekurangan anggota yang memerlukan pengganti pada tanggal tersebut.']);
        }

        if ($existingAssignments->contains(fn (Penugasan $assignment): bool => $assignment->anggota_id === $member->id)) {
            return back()->withErrors(['anggota_id' => 'Anggota ini sudah ditugaskan pada tanggal tersebut.']);
        }

        if ($conflicts->hasConflictOnDate($member->id, $jadwal, $date, $jadwal->id)) {
            return back()->withErrors(['anggota_id' => 'Anggota pengganti memiliki bentrok jadwal pada tanggal tersebut.']);
        }

        $replacement = Penugasan::create([
            'jadwal_id' => $jadwal->id,
            'anggota_id' => $member->id,
            'original_anggota_id' => $member->id,
            'tanggal_mulai' => $date->toDateString(),
            'tanggal_selesai' => $date->toDateString(),
            'status' => 'assigned',
            'sumber_penugasan' => 'hasil_perubahan',
            'assigned_at' => now(),
            'catatan_perubahan' => 'Pengganti harian untuk '.$date->format('d-m-Y').'.',
        ]);
        $statuses->refresh($jadwal);

        return redirect()->route('admin.jadwal.penugasan', $jadwal)
            ->with('success', 'Pengganti untuk '.$date->format('d-m-Y').' berhasil ditambahkan.');
    }

    public function show(Penugasan $penugasan, ScheduleConflictService $conflicts): View
    {
        $penugasan->load([
            'jadwal.jenisPekerjaan',
            'jadwal.penugasan.anggota',
            'jadwal.penugasan.presensiHarian',
            'jadwal.penugasan.presensi',
            'jadwal.penugasan.upah',
            'anggota',
            'presensi',
            'upah',
            'originalAnggota',
        ]);
        $anggotaPengganti = User::where('role', 'anggota')
            ->where('status_aktif', true)
            ->whereKeyNot($penugasan->anggota_id)
            ->whereDoesntHave('penugasan', fn ($query) => $query
                ->where('jadwal_id', $penugasan->jadwal_id)
                ->where('status', '!=', 'cancelled'))
            ->orderBy('nama_lengkap')
            ->get()
            ->reject(fn (User $candidate): bool => $conflicts->hasConflict($candidate->id, $penugasan->jadwal, $penugasan->jadwal_id));

        return view('admin.penugasan.show', compact('penugasan', 'anggotaPengganti'));
    }

    public function edit(Penugasan $penugasan): View
    {
        abort_unless(in_array($penugasan->status, ['assigned', 'in_progress'], true), 404);
        $anggota = User::where('role', 'anggota')->where('status_aktif', true)->get();

        return view('admin.penugasan.edit', compact('penugasan', 'anggota'));
    }

    public function remove(Request $request, Penugasan $penugasan, ScheduleConflictService $conflicts, JadwalStatusService $statuses): RedirectResponse
    {
        if (! in_array($penugasan->status, ['assigned', 'in_progress'], true)) {
            return back()->withErrors(['status' => 'Anggota tidak dapat dikeluarkan setelah laporan selesai dikirim.']);
        }

        $validated = $request->validate([
            'replacement_anggota_id' => ['nullable', 'exists:users,id'],
        ]);
        $replacementId = $validated['replacement_anggota_id'] ?? null;

        if ($replacementId && (int) $replacementId === $penugasan->anggota_id) {
            return back()->withErrors(['replacement_anggota_id' => 'Pilih anggota pengganti yang berbeda.']);
        }

        $replacement = $replacementId
            ? User::where('role', 'anggota')->where('status_aktif', true)->find($replacementId)
            : null;

        if ($replacementId && ! $replacement) {
            return back()->withErrors(['replacement_anggota_id' => 'Anggota pengganti harus aktif.']);
        }

        if ($replacement && Penugasan::where('jadwal_id', $penugasan->jadwal_id)
            ->where('anggota_id', $replacement->id)
            ->where('status', '!=', 'cancelled')
            ->exists()) {
            return back()->withErrors(['replacement_anggota_id' => 'Anggota pengganti sudah ada di kegiatan ini.']);
        }

        $conflictSchedule = clone $penugasan->jadwal;
        $conflictSchedule->tanggal = $penugasan->tanggalMulaiEfektif();
        $conflictSchedule->tanggal_selesai = $penugasan->tanggalSelesaiEfektif();
        if ($replacement && $conflicts->hasConflict($replacement->id, $conflictSchedule, $penugasan->jadwal_id)) {
            return back()->withErrors(['replacement_anggota_id' => 'Anggota pengganti memiliki bentrok jadwal pada waktu tersebut.']);
        }

        $otherAssignments = $penugasan->jadwal->penugasan()
            ->with('presensiHarian')
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $penugasan->id)
            ->get();
        foreach (CarbonPeriod::create($penugasan->jadwal->tanggal, $penugasan->jadwal->tanggal_selesai) as $date) {
            $coveredCount = $otherAssignments->filter(fn (Penugasan $assignment): bool => $assignment->tanggalMulaiEfektif()->lte($date)
                && $assignment->tanggalSelesaiEfektif()->gte($date)
                && ! in_array(
                    $assignment->presensiHarian->first(fn ($presence): bool => $presence->tanggal->isSameDay($date))?->status,
                    ['izin', 'tidak_hadir'],
                    true,
                ))->count();

            if ($coveredCount + ($replacement ? 1 : 0) < $penugasan->jadwal->jumlah_orang_dibutuhkan) {
                return back()->withErrors([
                    'replacement_anggota_id' => 'Pilih anggota pengganti untuk memenuhi kebutuhan pada '.$date->format('d-m-Y').'.',
                ]);
            }
        }

        DB::transaction(function () use ($penugasan, $replacement, $statuses): void {
            $penugasan->update([
                'status' => 'cancelled',
                'catatan_verifikasi' => $replacement
                    ? 'Anggota dikeluarkan dan diganti oleh '.$replacement->nama_lengkap.'.'
                    : 'Anggota dikeluarkan dari penugasan tanpa pengganti.',
            ]);

            if ($replacement) {
                Penugasan::create([
                    'jadwal_id' => $penugasan->jadwal_id,
                    'anggota_id' => $replacement->id,
                    'original_anggota_id' => $replacement->id,
                    'tanggal_mulai' => $penugasan->tanggal_mulai,
                    'tanggal_selesai' => $penugasan->tanggal_selesai,
                    'status' => 'assigned',
                    'sumber_penugasan' => 'hasil_perubahan',
                    'assigned_at' => now(),
                ]);
            }

            $statuses->refresh($penugasan->jadwal);
        });

        return redirect()->route('admin.penugasan.show', $penugasan)->with('success', $replacement
            ? 'Anggota dikeluarkan dan penggantinya telah ditugaskan.'
            : 'Anggota dikeluarkan. Posisi penugasan masih kosong.');
    }

    public function update(Request $request, Penugasan $penugasan, ScheduleConflictService $conflicts): RedirectResponse
    {
        $validated = $request->validate([
            'anggota_id' => ['required', 'exists:users,id'],
            'catatan_verifikasi' => ['nullable', 'string'],
        ]);

        if (! in_array($penugasan->status, ['assigned', 'in_progress'], true)) {
            return back()->withErrors(['status' => 'Anggota tidak dapat diubah setelah laporan selesai dikirim.']);
        }

        $newMember = User::where('role', 'anggota')->findOrFail($validated['anggota_id']);

        if ($newMember->status_aktif !== true) {
            return back()->withErrors(['anggota_id' => 'Anggota tidak aktif.']);
        }

        if (Penugasan::where('jadwal_id', $penugasan->jadwal_id)
            ->where('anggota_id', $newMember->id)
            ->where('id', '!=', $penugasan->id)
            ->where('status', '!=', 'cancelled')
            ->exists()) {
            return back()->withErrors(['anggota_id' => 'Anggota sudah memiliki penugasan aktif pada kegiatan ini.']);
        }

        $conflictSchedule = clone $penugasan->jadwal;
        $conflictSchedule->tanggal = $penugasan->tanggalMulaiEfektif();
        $conflictSchedule->tanggal_selesai = $penugasan->tanggalSelesaiEfektif();
        if ($conflicts->hasConflict($newMember->id, $conflictSchedule, $penugasan->jadwal_id)) {
            return back()->withErrors(['anggota_id' => 'Anggota memiliki bentrok jadwal.']);
        }

        $penugasan->update([
            'anggota_id' => $newMember->id,
            'sumber_penugasan' => 'manual',
            'catatan_verifikasi' => $validated['catatan_verifikasi'] ?? $penugasan->catatan_verifikasi,
        ]);

        return redirect()->route('admin.penugasan.show', $penugasan)->with('success', 'Penugasan berhasil diperbarui.');
    }

    public function verify(Penugasan $penugasan, UpahTugasService $upahService, JadwalStatusService $statuses): RedirectResponse
    {
        if ($penugasan->status !== 'waiting_verification') {
            return back()->withErrors(['status' => 'Status tugas tidak menunggu verifikasi.']);
        }

        try {
            DB::transaction(function () use ($penugasan, $upahService, $statuses): void {
                $penugasan = Penugasan::query()->lockForUpdate()->findOrFail($penugasan->id);
                if ($penugasan->status !== 'waiting_verification') {
                    throw new RuntimeException('Tugas ini tidak lagi menunggu verifikasi.');
                }

                $penugasan->update([
                    'status' => 'completed',
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                    'catatan_verifikasi' => 'Diperiksa dan disetujui admin.',
                ]);
                $upahService->distributeForCompletedSchedule($penugasan->jadwal);
                $statuses->refresh($penugasan->jadwal);
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors(['upah' => $exception->getMessage()]);
        }

        return redirect()->route('admin.penugasan.show', $penugasan)
            ->with('success', 'Laporan disetujui. Status upah kegiatan ini sudah diperbarui.');
    }
}
