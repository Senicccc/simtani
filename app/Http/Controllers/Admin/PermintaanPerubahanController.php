<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\PermintaanPerubahan;
use App\Services\AvailableScheduleService;
use App\Services\JadwalStatusService;
use App\Services\ScheduleConflictService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermintaanPerubahanController extends Controller
{
    public function index(Request $request): View
    {
        $permintaan = PermintaanPerubahan::with(['penugasan.anggota', 'penugasan.jadwal', 'diajukanOleh', 'targetJadwal', 'targetPenugasan.anggota', 'targetPenugasan.jadwal'])
            ->when(in_array($request->input('status'), ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.permintaan-perubahan.index', compact('permintaan'));
    }

    public function show(
        PermintaanPerubahan $permintaan,
        AvailableScheduleService $availableSchedules,
        ScheduleConflictService $conflicts,
        JadwalStatusService $statuses,
    ): View {
        $permintaan->load(['penugasan.anggota', 'penugasan.jadwal', 'diajukanOleh', 'targetJadwal', 'targetPenugasan.anggota', 'targetPenugasan.jadwal', 'diprosesOleh']);
        $availableTargets = $permintaan->jenis_permintaan === 'sanggah'
            ? $availableSchedules->forMember($permintaan->penugasan->anggota, $permintaan->penugasan->jadwal_id, $conflicts)
            : collect();

        return view('admin.permintaan-perubahan.show', compact('permintaan', 'availableTargets'));
    }

    public function process(
        Request $request,
        PermintaanPerubahan $permintaan,
        AvailableScheduleService $availableSchedules,
        ScheduleConflictService $conflicts,
    ): RedirectResponse {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'target_jadwal_id' => ['nullable', 'exists:jadwal,id'],
            'catatan_admin' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($validated['action'] === 'approve' && $permintaan->jenis_permintaan === 'tukar') {
            $validated['target_jadwal_id'] = $permintaan->target_jadwal_id;
        }

        $error = DB::transaction(function () use ($permintaan, $validated, $availableSchedules, $conflicts): ?string {
            $changeRequest = PermintaanPerubahan::query()->lockForUpdate()->findOrFail($permintaan->id);
            if ($changeRequest->status !== 'pending') {
                return 'Permintaan ini sudah pernah diproses.';
            }

            if ($validated['action'] === 'reject') {
                $changeRequest->update([
                    'status' => 'rejected',
                    'diproses_oleh' => auth()->id(),
                    'diproses_at' => now(),
                    'catatan_admin' => $validated['catatan_admin'] ?? null,
                ]);

                return null;
            }

            $source = Penugasan::query()->lockForUpdate()->findOrFail($changeRequest->penugasan_id);
            if ($source->status !== 'assigned' || $source->anggota_id !== $changeRequest->diajukan_oleh) {
                return 'Tugas sudah dimulai, berubah pemilik, atau tidak lagi dapat dipindahkan. Permintaan tidak diproses.';
            }

            $destinationId = $validated['target_jadwal_id'] ?? null;
            if ($changeRequest->jenis_permintaan === 'tukar' && ! $destinationId) {
                return 'Jadwal tujuan pindah belum dipilih.';
            }

            $destination = null;
            if ($destinationId) {
                $destination = Jadwal::query()
                    ->with(['jenisPekerjaan', 'penugasan'])
                    ->lockForUpdate()
                    ->findOrFail($destinationId);
                $member = $source->anggota()->firstOrFail();
                if (! $availableSchedules->isAvailable($member, $destination, $source->jadwal_id, $conflicts)) {
                    return 'Jadwal tujuan sudah penuh atau tidak sesuai kompetensi/waktu. Pilih tujuan lain.';
                }
            }

            $source->update([
                'status' => 'cancelled',
                'catatan_perubahan' => $destination
                    ? 'Dipindahkan melalui permintaan #'.$changeRequest->id.' ke '.$destination->nama_kegiatan.'.'
                    : 'Dibatalkan melalui sanggahan permintaan #'.$changeRequest->id.' tanpa pengganti.',
            ]);

            if ($destination) {
                Penugasan::create([
                    'jadwal_id' => $destination->id,
                    'anggota_id' => $source->anggota_id,
                    'original_anggota_id' => $source->anggota_id,
                    'status' => 'assigned',
                    'sumber_penugasan' => 'hasil_perubahan',
                    'assigned_at' => now(),
                ]);
            }

            $changeRequest->update([
                'target_jadwal_id' => $destination?->id,
                'status' => 'approved',
                'diproses_oleh' => auth()->id(),
                'diproses_at' => now(),
                'catatan_admin' => $validated['catatan_admin'] ?? null,
            ]);

            return null;
        });
        if ($error) {
            return back()->withErrors(['status' => $error]);
        }

        return redirect()->route('admin.permintaan-perubahan.index')
            ->with('success', $validated['action'] === 'reject'
                ? 'Permintaan ditolak. Tugas anggota tidak berubah.'
                : 'Permintaan disetujui dan tugas diperbarui.');
    }
}
