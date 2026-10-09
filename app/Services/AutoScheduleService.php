<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AutoScheduleService
{
    public function __construct(private JadwalStatusService $statuses) {}

    public function recommendationsForSchedule(Jadwal $jadwal, ScheduleConflictService $conflicts): array
    {
        $assignedCount = $this->statuses->dailyCoverage($jadwal)->min('anggota') ?? 0;
        $vacancies = max(0, (int) $jadwal->jumlah_orang_dibutuhkan - $assignedCount);
        $eligible = ! in_array($jadwal->status, ['completed', 'cancelled'], true);
        $candidates = $eligible && $vacancies > 0 ? $this->candidatesForSchedule($jadwal, $conflicts) : [];
        $recommendedCount = min($vacancies, count($candidates));

        return [
            'required_slots' => (int) $jadwal->jumlah_orang_dibutuhkan,
            'assigned_count' => $assignedCount,
            'vacancies' => $vacancies,
            'candidate_count' => count($candidates),
            'recommended_count' => $recommendedCount,
            'can_generate' => $eligible && $vacancies > 0 && $recommendedCount > 0,
            'is_shortage' => count($candidates) < $vacancies,
            'unavailable_reason' => $this->unavailableReason($jadwal, $candidates),
            'recommended_member_ids' => array_column(array_slice($candidates, 0, $recommendedCount), 'anggota_id'),
            'candidates' => $candidates,
        ];
    }

    /**
     * @param  array<int, int|string>|null  $selectedMemberIds
     * @return array<int, array<string, mixed>>
     */
    public function generateForSchedule(
        Jadwal $jadwal,
        ScheduleConflictService $conflicts,
        ?array $selectedMemberIds = null,
    ): array {
        return DB::transaction(function () use ($jadwal, $conflicts, $selectedMemberIds): array {
            $jadwal = Jadwal::query()->lockForUpdate()->findOrFail($jadwal->id);
            if (in_array($jadwal->status, ['completed', 'cancelled'], true)) {
                throw new RuntimeException('Penugasan otomatis hanya tersedia untuk jadwal yang belum selesai atau dibatalkan.');
            }

            $recommendations = $this->recommendationsForSchedule($jadwal, $conflicts);
            if ($recommendations['vacancies'] === 0) {
                return [];
            }
            if (! $recommendations['can_generate']) {
                throw new RuntimeException($recommendations['unavailable_reason']);
            }

            $selectedMemberIds ??= $recommendations['recommended_member_ids'];
            $selectedMemberIds = array_values(array_unique(array_map('intval', $selectedMemberIds)));
            if ($selectedMemberIds === [] || count($selectedMemberIds) > $recommendations['vacancies']) {
                throw new RuntimeException('Pilih anggota sesuai jumlah slot yang tersedia.');
            }

            if (! $recommendations['is_shortage'] && count($selectedMemberIds) !== $recommendations['vacancies']) {
                throw new RuntimeException('Pilih tepat '.$recommendations['vacancies'].' anggota untuk mengisi semua slot.');
            }

            $candidatesById = collect($recommendations['candidates'])->keyBy('anggota_id');
            foreach ($selectedMemberIds as $memberId) {
                if (! $candidatesById->has($memberId)) {
                    throw new RuntimeException('Ada anggota yang tidak lagi tersedia. Muat ulang rekomendasi sebelum mencoba lagi.');
                }
            }

            $selected = [];
            foreach ($selectedMemberIds as $memberId) {
                $candidate = $candidatesById->get($memberId);
                $member = User::query()->findOrFail($memberId);

                Penugasan::create([
                    'jadwal_id' => $jadwal->id,
                    'anggota_id' => $member->id,
                    'original_anggota_id' => $member->id,
                    'status' => 'assigned',
                    'sumber_penugasan' => 'otomatis',
                    'assigned_at' => now(),
                ]);

                $selected[] = $candidate;
            }

            return $selected;
        });
    }

    /**
     * @return array<int, array{anggota_id: int, nama_lengkap: string, nomor_anggota: string, tingkat_kemampuan: ?string, jumlah_tugas_aktif: int, jumlah_tugas_selesai: int, reason: string}>
     */
    public function manualRecommendationsForSchedule(Jadwal $jadwal, ScheduleConflictService $conflicts): array
    {
        return $this->candidatesForSchedule($jadwal, $conflicts);
    }

    /**
     * @return array<int, array{anggota_id: int, nama_lengkap: string, nomor_anggota: string, tingkat_kemampuan: ?string, jumlah_tugas_aktif: int, jumlah_tugas_selesai: int, reason: string}>
     */
    private function candidatesForSchedule(Jadwal $jadwal, ScheduleConflictService $conflicts): array
    {
        $jadwal->loadMissing('jenisPekerjaan');
        $members = User::query()
            ->where('role', 'anggota')
            ->where('status_aktif', true)
            ->withCount([
                'penugasan as active_task_count' => fn ($query) => $query->whereIn('status', ['assigned', 'in_progress', 'waiting_verification']),
                'penugasan as completed_task_count' => fn ($query) => $query->where('status', 'completed'),
            ])
            ->withMax([
                'penugasan as last_completed_at' => fn ($query) => $query->where('status', 'completed'),
            ], 'completed_at')
            ->with(['kompetensi' => fn ($query) => $query->where('jenis_pekerjaan_id', $jadwal->jenis_pekerjaan_id)])
            ->get();
        $candidates = [];

        foreach ($members as $member) {
            if ($conflicts->hasConflict($member->id, $jadwal, $jadwal->id)
                || $jadwal->penugasan()->where('anggota_id', $member->id)->where('status', '!=', 'cancelled')->exists()) {
                continue;
            }

            $kompetensi = $member->kompetensi->first();
            $activeTaskCount = (int) $member->active_task_count;
            $completedTaskCount = (int) $member->completed_task_count;
            $candidates[] = [
                'anggota_id' => $member->id,
                'nama_lengkap' => $member->nama_lengkap,
                'nomor_anggota' => $member->nomor_anggota,
                'tingkat_kemampuan' => $kompetensi?->tingkat_kemampuan,
                'jumlah_tugas_aktif' => $activeTaskCount,
                'jumlah_tugas_selesai' => $completedTaskCount,
                'last_completed_at' => $member->last_completed_at,
                'skill_order' => $this->abilityOrder($kompetensi?->tingkat_kemampuan),
                'reason' => $this->candidateReason($activeTaskCount, $completedTaskCount, $kompetensi?->tingkat_kemampuan),
            ];
        }

        usort($candidates, function (array $a, array $b): int {
            foreach (['jumlah_tugas_aktif', 'jumlah_tugas_selesai'] as $key) {
                if ($a[$key] !== $b[$key]) {
                    return $a[$key] <=> $b[$key];
                }
            }

            if ($a['last_completed_at'] !== $b['last_completed_at']) {
                $aTime = $a['last_completed_at'] ? Carbon::parse($a['last_completed_at']) : Carbon::create(1970, 1, 1);
                $bTime = $b['last_completed_at'] ? Carbon::parse($b['last_completed_at']) : Carbon::create(1970, 1, 1);

                return $aTime->timestamp <=> $bTime->timestamp;
            }

            if ($a['skill_order'] !== $b['skill_order']) {
                return $b['skill_order'] <=> $a['skill_order'];
            }

            return $a['anggota_id'] <=> $b['anggota_id'];
        });

        return array_map(static function (array $candidate): array {
            unset($candidate['last_completed_at'], $candidate['skill_order']);

            return $candidate;
        }, $candidates);
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     */
    private function unavailableReason(Jadwal $jadwal, array $candidates): string
    {
        if (in_array($jadwal->status, ['completed', 'cancelled'], true)) {
            return 'Penugasan otomatis tidak tersedia karena tugas sudah selesai atau dibatalkan.';
        }

        if ($candidates !== []) {
            return '';
        }

        if (User::query()->where('role', 'anggota')->where('status_aktif', true)->doesntExist()) {
            return 'Belum ada anggota aktif. Aktifkan anggota terlebih dahulu.';
        }

        return 'Belum ada anggota aktif yang tersedia. Periksa penugasan lain pada tanggal yang sama.';
    }

    private function candidateReason(int $activeTasks, int $completedTasks, ?string $skillLevel): string
    {
        $reason = $activeTasks.' tugas aktif, '.$completedTasks.' tugas selesai';

        return $skillLevel
            ? $reason.', kompetensi '.$skillLevel
            : $reason.', belum ada kompetensi yang tercatat';
    }

    private function abilityOrder(?string $level): int
    {
        return match ($level) {
            'pemula' => 1,
            'menengah' => 2,
            'mahir' => 3,
            default => 0,
        };
    }
}
