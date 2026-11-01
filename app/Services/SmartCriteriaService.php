<?php

namespace App\Services;

use App\Models\Jadwal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SmartCriteriaService
{
    /**
     * @param  array<int, int>  $memberIds
     * @param  array<int, string>|null  $statuses
     * @return array<int, float>
     */
    public function workloadsForPeriod(
        Carbon $periodStart,
        Carbon $periodEnd,
        array $memberIds,
        ?array $statuses = ['assigned', 'in_progress', 'waiting_verification', 'completed'],
        ?int $excludedScheduleId = null,
    ): array {
        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        if ($memberIds === []) {
            return [];
        }

        $periodStart = $periodStart->copy()->startOfDay();
        $periodEnd = $periodEnd->copy()->startOfDay();
        $workloads = array_fill_keys($memberIds, 0.0);
        $assignments = DB::table('penugasan as p')
            ->join('jadwal as j', 'j.id', '=', 'p.jadwal_id')
            ->whereIn('p.anggota_id', $memberIds)
            ->where('j.tanggal', '<=', $periodEnd->toDateString())
            ->when(
                $statuses === null,
                fn ($query) => $query->where('p.status', '!=', 'cancelled'),
                fn ($query) => $query->whereIn('p.status', $statuses),
            )
            ->when($excludedScheduleId !== null, fn ($query) => $query->where('p.jadwal_id', '!=', $excludedScheduleId))
            ->select([
                'p.anggota_id',
                'p.tanggal_mulai as assignment_start',
                'p.tanggal_selesai as assignment_end',
                'j.tanggal as schedule_start',
                'j.tanggal_selesai as schedule_end',
                'j.waktu_mulai',
                'j.waktu_selesai',
            ])
            ->get();

        foreach ($assignments as $assignment) {
            $assignmentStart = Carbon::parse($assignment->assignment_start ?? $assignment->schedule_start)->startOfDay();
            $assignmentEnd = Carbon::parse($assignment->assignment_end ?? $assignment->schedule_end ?? $assignment->schedule_start)->startOfDay();

            if ($assignmentStart->gt($periodEnd) || $assignmentEnd->lt($periodStart)) {
                continue;
            }

            $overlapStart = $assignmentStart->greaterThan($periodStart) ? $assignmentStart : $periodStart;
            $overlapEnd = $assignmentEnd->lessThan($periodEnd) ? $assignmentEnd : $periodEnd;
            $overlapDays = (int) $overlapStart->diffInDays($overlapEnd) + 1;
            $hoursPerDay = $this->hoursPerDay($assignment->waktu_mulai, $assignment->waktu_selesai);

            $workloads[$assignment->anggota_id] += $hoursPerDay * $overlapDays;
        }

        return $workloads;
    }

    /**
     * @param  array<int, int>  $memberIds
     * @return array<int, array{beban: float, jeda: int, kompetensi: int, kehadiran: float}>
     */
    public function forCandidates(Jadwal $jadwal, array $memberIds): array
    {
        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        if ($memberIds === []) {
            return [];
        }

        $scheduleStart = Carbon::parse($jadwal->tanggal->toDateString())->startOfDay();
        $scheduleEnd = Carbon::parse(($jadwal->tanggal_selesai ?? $jadwal->tanggal)->toDateString())->startOfDay();
        $loadWindowStart = $scheduleStart->copy()->subDays((int) config('smart.jendela_beban_mundur_hari', 30));
        $loadWindowEnd = $scheduleEnd->copy()->addDays((int) config('smart.jendela_beban_maju_hari', 30));
        $attendanceWindowStart = $scheduleStart->copy()->subDays((int) config('smart.jendela_kehadiran_hari', 90));
        $maximumGap = (int) config('smart.jeda_maksimum_hari', 30);

        $criteria = [];
        foreach ($memberIds as $memberId) {
            $criteria[$memberId] = [
                'beban' => 0.0,
                'jeda' => $maximumGap,
                'kompetensi' => 1,
                'kehadiran' => 1.0,
            ];
        }

        foreach ($this->workloadsForPeriod(
            $loadWindowStart,
            $loadWindowEnd,
            $memberIds,
            ['assigned', 'in_progress', 'waiting_verification', 'completed'],
            $jadwal->id,
        ) as $memberId => $load) {
            $criteria[$memberId]['beban'] = $load;
        }

        $assignments = DB::table('penugasan as p')
            ->join('jadwal as j', 'j.id', '=', 'p.jadwal_id')
            ->whereIn('p.anggota_id', $memberIds)
            ->where('p.status', '!=', 'cancelled')
            ->where('p.jadwal_id', '!=', $jadwal->id)
            ->select([
                'p.anggota_id',
                'p.status',
                'p.tanggal_mulai as assignment_start',
                'p.tanggal_selesai as assignment_end',
                'j.tanggal as schedule_start',
                'j.tanggal_selesai as schedule_end',
                'j.waktu_mulai',
                'j.waktu_selesai',
            ])
            ->get();

        foreach ($assignments as $assignment) {
            $assignmentStart = Carbon::parse($assignment->assignment_start ?? $assignment->schedule_start)->startOfDay();
            $assignmentEnd = Carbon::parse($assignment->assignment_end ?? $assignment->schedule_end ?? $assignment->schedule_start)->startOfDay();

            if ($assignmentEnd->lte($scheduleStart)) {
                $gap = min($maximumGap, (int) $assignmentEnd->diffInDays($scheduleStart));
                $criteria[$assignment->anggota_id]['jeda'] = min($criteria[$assignment->anggota_id]['jeda'], $gap);
            }
        }

        $competencies = DB::table('kompetensi_anggota')
            ->whereIn('anggota_id', $memberIds)
            ->where('jenis_pekerjaan_id', $jadwal->jenis_pekerjaan_id)
            ->get(['anggota_id', 'tingkat_kemampuan']);

        foreach ($competencies as $competency) {
            $criteria[$competency->anggota_id]['kompetensi'] = match ($competency->tingkat_kemampuan) {
                'pemula' => 1,
                'menengah' => 2,
                'mahir' => 3,
                default => 1,
            };
        }

        $attendance = DB::table('presensi as pr')
            ->join('penugasan as p', 'p.id', '=', 'pr.penugasan_id')
            ->whereIn('p.anggota_id', $memberIds)
            ->whereDate('pr.tanggal', '>=', $attendanceWindowStart->toDateString())
            ->whereDate('pr.tanggal', '<', $scheduleStart->toDateString())
            ->whereIn('pr.status', ['hadir', 'tidak_hadir'])
            ->get(['p.anggota_id', 'pr.status']);

        $attendanceCounts = [];
        foreach ($attendance as $record) {
            $attendanceCounts[$record->anggota_id][$record->status] =
                ($attendanceCounts[$record->anggota_id][$record->status] ?? 0) + 1;
        }

        foreach ($memberIds as $memberId) {
            $present = $attendanceCounts[$memberId]['hadir'] ?? 0;
            $absent = $attendanceCounts[$memberId]['tidak_hadir'] ?? 0;
            $totalCounted = $present + $absent;
            $criteria[$memberId]['kehadiran'] = $totalCounted === 0 ? 1.0 : $present / $totalCounted;
        }

        return $criteria;
    }

    private function hoursPerDay(?string $startTime, ?string $endTime): float
    {
        if (! $startTime || ! $endTime) {
            return (float) config('smart.jam_bawaan_per_hari', 6);
        }

        $referenceDate = '2000-01-01 ';
        $start = Carbon::parse($referenceDate.$startTime);
        $end = Carbon::parse($referenceDate.$endTime);
        $durationHours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;

        return $durationHours > 0 ? $durationHours : (float) config('smart.jam_bawaan_per_hari', 6);
    }
}
