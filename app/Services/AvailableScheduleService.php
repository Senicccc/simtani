<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Support\Collection;

class AvailableScheduleService
{
    public function forMember(User $member, int $excludedScheduleId, ScheduleConflictService $conflicts): Collection
    {
        return Jadwal::query()
            ->with(['jenisPekerjaan', 'penugasan'])
            ->where('id', '!=', $excludedScheduleId)
            ->whereIn('status', ['draft', 'scheduled'])
            ->whereDate('tanggal', '>=', today())
            ->get()
            ->filter(fn (Jadwal $schedule): bool => $this->isAvailableForSchedule(
                $member,
                $schedule,
                $excludedScheduleId,
                $conflicts,
            ))
            ->values();
    }

    public function isAvailable(
        User $member,
        Jadwal $schedule,
        int $excludedScheduleId,
        ScheduleConflictService $conflicts,
    ): bool {
        return $this->isAvailableForSchedule($member, $schedule, $excludedScheduleId, $conflicts);
    }

    private function isAvailableForSchedule(
        User $member,
        Jadwal $schedule,
        int $excludedScheduleId,
        ScheduleConflictService $conflicts,
    ): bool {
        if ($member->role !== 'anggota'
            || ! $member->status_aktif
            || $schedule->id === $excludedScheduleId
            || ! in_array($schedule->status, ['draft', 'scheduled'], true)
            || $schedule->tanggal->lt(today())) {
            return false;
        }

        $activeAssignments = $schedule->penugasan
            ->whereIn('status', ['assigned', 'in_progress', 'waiting_verification', 'completed'])
            ->count();
        if ($activeAssignments >= $schedule->jumlah_orang_dibutuhkan) {
            return false;
        }

        $alreadyAssigned = Penugasan::query()
            ->where('jadwal_id', $schedule->id)
            ->where('anggota_id', $member->id)
            ->where('status', '!=', 'cancelled')
            ->exists();

        return ! $alreadyAssigned && ! $conflicts->hasConflict($member->id, $schedule, $excludedScheduleId);
    }
}
