<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\Presensi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class JadwalStatusService
{
    public function refresh(Jadwal $jadwal): void
    {
        if (in_array($jadwal->status, ['draft', 'cancelled'], true)) {
            return;
        }

        $assignments = $jadwal->penugasan()
            ->with('presensiHarian')
            ->where('status', '!=', 'cancelled')
            ->get();
        $coverageComplete = $assignments->isNotEmpty();

        foreach (CarbonPeriod::create($jadwal->tanggal, $jadwal->tanggal_selesai ?? $jadwal->tanggal) as $date) {
            $coveredCount = $assignments->filter(fn (Penugasan $assignment): bool => $this->coversDate($assignment, $date)
                && ! in_array(
                    $this->presenceForDate($assignment, $date)?->status,
                    ['izin', 'tidak_hadir'],
                    true,
                ))->count();

            if ($coveredCount < $jadwal->jumlah_orang_dibutuhkan) {
                $coverageComplete = false;
                break;
            }
        }

        if ($coverageComplete && $assignments->every(fn (Penugasan $assignment): bool => $assignment->status === 'completed')) {
            $status = 'completed';
        } elseif ($assignments->contains(fn (Penugasan $assignment): bool => in_array($assignment->status, ['in_progress', 'waiting_verification', 'completed'], true))
            || today()->greaterThanOrEqualTo($jadwal->tanggal)) {
            $status = 'ongoing';
        } else {
            $status = 'scheduled';
        }

        if ($jadwal->status !== $status) {
            $jadwal->update(['status' => $status]);
        }
    }

    /**
     * @return Collection<int, array{tanggal: Carbon, anggota: int, kebutuhan: int, izin: int}>
     */
    public function dailyCoverage(Jadwal $jadwal): Collection
    {
        $assignments = $jadwal->penugasan()
            ->with('presensiHarian')
            ->where('status', '!=', 'cancelled')
            ->get();

        return collect(CarbonPeriod::create($jadwal->tanggal, $jadwal->tanggal_selesai ?? $jadwal->tanggal))
            ->map(function (Carbon $date) use ($assignments, $jadwal): array {
                $onDate = $assignments->filter(fn (Penugasan $assignment): bool => $this->coversDate($assignment, $date));
                $excused = $onDate->filter(fn (Penugasan $assignment): bool => in_array(
                    $this->presenceForDate($assignment, $date)?->status,
                    ['izin', 'tidak_hadir'],
                    true,
                ));

                return [
                    'tanggal' => $date,
                    'anggota' => $onDate->count() - $excused->count(),
                    'kebutuhan' => (int) $jadwal->jumlah_orang_dibutuhkan,
                    'izin' => $excused->count(),
                ];
            });
    }

    public function pendingReplacementSlots(Jadwal $jadwal): int
    {
        return $this->dailyCoverage($jadwal)
            ->sum(fn (array $day): int => $day['izin'] > 0
                ? max(0, $day['kebutuhan'] - $day['anggota'])
                : 0);
    }

    private function coversDate(Penugasan $assignment, Carbon $date): bool
    {
        return $assignment->tanggalMulaiEfektif()->toDateString() <= $date->toDateString()
            && $assignment->tanggalSelesaiEfektif()->toDateString() >= $date->toDateString();
    }

    private function presenceForDate(Penugasan $assignment, Carbon $date): ?Presensi
    {
        return $assignment->presensiHarian->first(
            fn (Presensi $presence): bool => $presence->tanggal->toDateString() === $date->toDateString(),
        );
    }
}
