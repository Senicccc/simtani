<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AutoScheduleService
{
    public function generateForSchedule(Jadwal $jadwal): array
    {
        return DB::transaction(function () use ($jadwal) {
            $jobTypeId = $jadwal->jenis_pekerjaan_id;

            $eligibleMembers = User::query()
                ->where('role', 'anggota')
                ->where('status_aktif', true)
                ->whereHas('kompetensi', function ($query) use ($jobTypeId) {
                    $query->where('jenis_pekerjaan_id', $jobTypeId);
                })
                ->with(['kompetensi' => function ($query) use ($jobTypeId) {
                    $query->where('jenis_pekerjaan_id', $jobTypeId);
                }])
                ->get();

            $candidates = [];

            foreach ($eligibleMembers as $member) {
                $kompetensi = $member->kompetensi->first();

                if (! $kompetensi) {
                    continue;
                }

                if ($this->hasConflict($member->id, $jadwal)) {
                    continue;
                }

                if (Penugasan::where('jadwal_id', $jadwal->id)->where('anggota_id', $member->id)->exists()) {
                    continue;
                }

                $completedTaskCount = Penugasan::where('anggota_id', $member->id)
                    ->where('status', 'completed')
                    ->count();

                $lastCompletedAt = Penugasan::where('anggota_id', $member->id)
                    ->where('status', 'completed')
                    ->max('completed_at');

                $candidates[] = [
                    'member' => $member,
                    'kompetensi' => $kompetensi,
                    'completed_task_count' => $completedTaskCount,
                    'last_completed_at' => $lastCompletedAt,
                    'skill_order' => $this->abilityOrder($kompetensi->tingkat_kemampuan),
                    'reason' => 'Memenuhi kompetensi '.$jadwal->jenisPekerjaan->nama_pekerjaan.' dan memiliki jumlah tugas selesai lebih sedikit.',
                ];
            }

            usort($candidates, function ($a, $b) {
                if ($a['completed_task_count'] !== $b['completed_task_count']) {
                    return $a['completed_task_count'] <=> $b['completed_task_count'];
                }

                if ($a['last_completed_at'] !== $b['last_completed_at']) {
                    $aTime = $a['last_completed_at'] ? Carbon::parse($a['last_completed_at']) : Carbon::create(1970, 1, 1);
                    $bTime = $b['last_completed_at'] ? Carbon::parse($b['last_completed_at']) : Carbon::create(1970, 1, 1);

                    return $bTime->timestamp <=> $aTime->timestamp;
                }

                if ($a['skill_order'] !== $b['skill_order']) {
                    return $b['skill_order'] <=> $a['skill_order'];
                }

                return $a['member']->id <=> $b['member']->id;
            });

            $needed = (int) $jadwal->jumlah_orang_dibutuhkan;
            if (count($candidates) < $needed) {
                throw new \RuntimeException('Anggota yang memenuhi kompetensi hanya '.count($candidates).' orang, sedangkan kebutuhan '.$needed.' orang.');
            }

            $selected = array_slice($candidates, 0, $needed);

            foreach ($selected as $candidate) {
                Penugasan::create([
                    'jadwal_id' => $jadwal->id,
                    'anggota_id' => $candidate['member']->id,
                    'original_anggota_id' => $candidate['member']->id,
                    'status' => 'assigned',
                    'sumber_penugasan' => 'otomatis',
                    'assigned_at' => now(),
                ]);
            }

            return $selected;
        });
    }

    protected function hasConflict(int $memberId, Jadwal $jadwal): bool
    {
        $startDate = Carbon::parse($jadwal->tanggal);
        $endDate = Carbon::parse($jadwal->tanggal);

        $query = Penugasan::query()
            ->where('anggota_id', $memberId)
            ->whereIn('status', ['assigned', 'in_progress', 'waiting_verification'])
            ->join('jadwal', 'penugasan.jadwal_id', '=', 'jadwal.id');

        if ($jadwal->waktu_mulai && $jadwal->waktu_selesai) {
            $query->whereDate('jadwal.tanggal', $startDate->toDateString())
                ->where(function ($q) use ($jadwal) {
                    $q->where(function ($sub) use ($jadwal) {
                        $sub->where('jadwal.waktu_mulai', '<', $jadwal->waktu_selesai)
                            ->where('jadwal.waktu_selesai', '>', $jadwal->waktu_mulai);
                    });
                });
        } else {
            $query->whereDate('jadwal.tanggal', $startDate->toDateString());
        }

        return $query->exists();
    }

    protected function abilityOrder(string $level): int
    {
        return match ($level) {
            'pemula' => 1,
            'menengah' => 2,
            'mahir' => 3,
            default => 0,
        };
    }
}
