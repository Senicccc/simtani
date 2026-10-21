<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\Upah;
use RuntimeException;

class UpahTugasService
{
    public function distributeForCompletedSchedule(Jadwal $jadwal): bool
    {
        $assignments = $jadwal->penugasan()
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->get();

        if ($assignments->isEmpty() || $assignments->contains(fn (Penugasan $assignment): bool => ! in_array($assignment->status, ['completed', 'rejected'], true))) {
            return false;
        }

        $eligibleAssignments = $assignments->where('status', 'completed')->values();
        if ($eligibleAssignments->isEmpty()) {
            return false;
        }

        $totalCents = (int) round((float) $jadwal->total_upah * 100);
        if ($totalCents <= 0) {
            throw new RuntimeException('Total upah kegiatan belum ditentukan. Minta admin melengkapi total upah sebelum menyelesaikan tugas.');
        }

        $shareCents = intdiv($totalCents, $eligibleAssignments->count());
        $remainderCents = $totalCents % $eligibleAssignments->count();

        foreach ($eligibleAssignments as $index => $assignment) {
            $upah = $assignment->upah()->first();
            if ($upah?->status_pembayaran === 'sudah_dibayar') {
                continue;
            }

            Upah::updateOrCreate(
                ['penugasan_id' => $assignment->id],
                [
                    'mode_perhitungan' => 'manual',
                    'jumlah_upah' => ($shareCents + ($index < $remainderCents ? 1 : 0)) / 100,
                    'status_pembayaran' => 'belum_dibayar',
                    'catatan' => 'Bagian upah dibagi rata kepada anggota yang laporannya disetujui.',
                ],
            );
        }

        return true;
    }
}
