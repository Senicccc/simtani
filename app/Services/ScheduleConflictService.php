<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Penugasan;
use Illuminate\Support\Carbon;

class ScheduleConflictService
{
    public function hasConflict(int $memberId, Jadwal $schedule, int|array|null $ignoredScheduleId = null): bool
    {
        $ignoredScheduleIds = is_array($ignoredScheduleId)
            ? $ignoredScheduleId
            : array_filter([$ignoredScheduleId]);

        return Penugasan::query()
            ->join('jadwal as conflicting_jadwal', 'penugasan.jadwal_id', '=', 'conflicting_jadwal.id')
            ->where('penugasan.anggota_id', $memberId)
            ->whereIn('penugasan.status', ['assigned', 'in_progress', 'waiting_verification'])
            ->whereRaw('DATE(COALESCE(penugasan.tanggal_mulai, conflicting_jadwal.tanggal)) <= ?', [
                ($schedule->tanggal_selesai ?? $schedule->tanggal)->toDateString(),
            ])
            ->whereRaw('DATE(COALESCE(penugasan.tanggal_selesai, conflicting_jadwal.tanggal_selesai, conflicting_jadwal.tanggal)) >= ?', [
                $schedule->tanggal->toDateString(),
            ])
            ->when($ignoredScheduleIds !== [], fn ($query) => $query->whereNotIn('conflicting_jadwal.id', $ignoredScheduleIds))
            ->where(function ($query) use ($schedule): void {
                if (! $schedule->waktu_mulai || ! $schedule->waktu_selesai) {
                    return;
                }

                $query->whereNull('conflicting_jadwal.waktu_mulai')
                    ->orWhereNull('conflicting_jadwal.waktu_selesai')
                    ->orWhere(function ($query) use ($schedule): void {
                        $query->where('conflicting_jadwal.waktu_mulai', '<', $schedule->waktu_selesai)
                            ->where('conflicting_jadwal.waktu_selesai', '>', $schedule->waktu_mulai);
                    });
            })
            ->exists();
    }

    public function hasConflictOnDate(int $memberId, Jadwal $schedule, Carbon $date, ?int $ignoredScheduleId = null): bool
    {
        $singleDaySchedule = clone $schedule;
        $singleDaySchedule->tanggal = $date;
        $singleDaySchedule->tanggal_selesai = $date;

        return $this->hasConflict($memberId, $singleDaySchedule, $ignoredScheduleId);
    }
}
