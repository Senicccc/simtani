<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\PermintaanPerubahan;
use App\Services\AvailableScheduleService;
use App\Services\ScheduleConflictService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PermintaanPerubahanController extends Controller
{
    public function index(): View
    {
        $permintaan = PermintaanPerubahan::with(['penugasan.jadwal', 'targetJadwal', 'targetPenugasan.jadwal', 'targetPenugasan.anggota', 'diprosesOleh'])
            ->where('diajukan_oleh', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('anggota.permintaan-perubahan.index', compact('permintaan'));
    }

    public function create(AvailableScheduleService $availableSchedules, ScheduleConflictService $conflicts): View
    {
        $penugasan = Penugasan::with(['jadwal.jenisPekerjaan'])
            ->where('anggota_id', Auth::id())
            ->where('status', 'assigned')
            ->whereDoesntHave('permintaanPerubahan', fn ($query) => $query->where('status', 'pending'))
            ->get();
        $availableTargets = collect();
        $member = Auth::user();

        foreach ($penugasan as $assignment) {
            foreach ($availableSchedules->forMember($member, $assignment->jadwal_id, $conflicts) as $schedule) {
                $availableTargets->push([
                    'assignment_id' => $assignment->id,
                    'schedule' => $schedule,
                ]);
            }
        }
        $availableTargets = $availableTargets->unique(fn (array $target): string => $target['assignment_id'].':'.$target['schedule']->id)->values();

        return view('anggota.permintaan-perubahan.create', compact('penugasan', 'availableTargets'));
    }

    public function store(Request $request, AvailableScheduleService $availableSchedules, ScheduleConflictService $conflicts): RedirectResponse
    {
        $validated = $request->validate([
            'penugasan_id' => ['required', 'exists:penugasan,id'],
            'jenis_permintaan' => ['required', 'in:tukar,sanggah'],
            'target_jadwal_id' => ['required_if:jenis_permintaan,tukar', 'nullable', 'exists:jadwal,id'],
            'alasan' => ['required', 'string', 'max:2000'],
        ]);
        $penugasan = Penugasan::with('jadwal')->findOrFail($validated['penugasan_id']);
        abort_unless($penugasan->anggota_id === Auth::id(), 403);
        if ($penugasan->status !== 'assigned' || $penugasan->presensiHarian()->whereNotNull('waktu_check_in')->exists()) {
            return back()->withInput()->withErrors(['penugasan_id' => 'Permintaan hanya dapat diajukan untuk tugas yang belum dimulai.']);
        }
        if (! Auth::user()->status_aktif) {
            return back()->withInput()->withErrors(['penugasan_id' => 'Akun anggota tidak aktif. Hubungi admin.']);
        }

        if ($penugasan->permintaanPerubahan()->where('status', 'pending')->exists()) {
            return back()->withInput()->withErrors(['penugasan_id' => 'Tugas ini sudah memiliki permintaan perubahan yang menunggu keputusan admin.']);
        }

        $targetSchedule = isset($validated['target_jadwal_id'])
            ? Jadwal::with(['jenisPekerjaan', 'penugasan'])->findOrFail($validated['target_jadwal_id'])
            : null;
        if ($targetSchedule && ! $availableSchedules->isAvailable(Auth::user(), $targetSchedule, $penugasan->jadwal_id, $conflicts)) {
            return back()->withInput()->withErrors(['target_jadwal_id' => 'Jadwal tujuan tidak tersedia atau tidak sesuai kompetensi dan waktu. Pilih jadwal lain.']);
        }

        PermintaanPerubahan::create([
            'penugasan_id' => $penugasan->id,
            'diajukan_oleh' => Auth::id(),
            'jenis_permintaan' => $validated['jenis_permintaan'],
            'target_jadwal_id' => $targetSchedule?->id,
            'alasan' => $validated['alasan'],
            'status' => 'pending',
        ]);

        return redirect()->route('anggota.permintaan-perubahan.index')->with('success', 'Permintaan perubahan berhasil dikirim.');
    }
}
