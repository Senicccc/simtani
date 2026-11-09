<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\KompetensiAnggota;
use App\Models\Penugasan;
use App\Models\Presensi;
use App\Models\User;
use App\Services\UpahTugasService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmartDemoSeeder extends Seeder
{
    private const SEED = 20261102;

    private const WORK_TYPES = [
        'Penanaman',
        'Pemupukan',
        'Panen',
        'Pengendalian Hama',
        'Pembersihan Lahan',
    ];

    private const MEMBER_NUMBERS = [
        'ANG-001', 'ANG-002', 'ANG-003', 'ANG-004', 'ANG-005',
        'ANG-006', 'ANG-007', 'ANG-008',
        'ANG-010', 'ANG-011', 'ANG-012', 'ANG-013', 'ANG-014',
        'ANG-015', 'ANG-016', 'ANG-017', 'ANG-018', 'ANG-019',
        'ANG-020', 'ANG-021', 'ANG-022', 'ANG-023', 'ANG-024',
        'ANG-025', 'ANG-026',
    ];

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Data simulasi hanya dapat dibuat di lingkungan local atau testing.');
        }

        DB::transaction(function (): void {
            $admin = User::query()->where('nomor_anggota', 'ADM-001')->firstOrFail();
            if (Jadwal::query()->where('nama_kegiatan', 'not like', 'SIMULASI SMART-ROC%')->exists()) {
                throw new RuntimeException('Seeder simulasi hanya boleh dijalankan pada database local/testing khusus yang tidak berisi jadwal lain.');
            }

            $jobs = JenisPekerjaan::query()
                ->whereIn('nama_pekerjaan', self::WORK_TYPES)
                ->orderBy('nama_pekerjaan')
                ->get()
                ->keyBy('nama_pekerjaan');
            $members = User::query()
                ->whereIn('nomor_anggota', self::MEMBER_NUMBERS)
                ->where('role', 'anggota')
                ->where('status_aktif', true)
                ->orderBy('nomor_anggota')
                ->get()
                ->keyBy('nomor_anggota');
            $inactiveMember = User::query()->where('nomor_anggota', 'ANG-009')->first();

            if ($jobs->count() !== 5 || $members->count() !== count(self::MEMBER_NUMBERS)) {
                throw new RuntimeException('Lima jenis pekerjaan dan 25 anggota simulasi aktif harus tersedia.');
            }
            if ($inactiveMember === null || $inactiveMember->role !== 'anggota' || $inactiveMember->status_aktif) {
                throw new RuntimeException('ANG-009 harus tetap terdaftar sebagai anggota nonaktif.');
            }

            $this->seedCompetencies($members->values(), $jobs->values());

            foreach ($this->activities() as $activity) {
                $job = $jobs->get($activity['jenis_pekerjaan']);
                if ($job === null) {
                    throw new RuntimeException('Jenis pekerjaan simulasi tidak ditemukan: '.$activity['jenis_pekerjaan']);
                }

                $schedule = Jadwal::query()->where('nama_kegiatan', $activity['nama_kegiatan'])->first();
                if ($schedule && (int) $schedule->created_by !== (int) $admin->id) {
                    throw new RuntimeException('Nama jadwal simulasi sudah digunakan oleh data lain: '.$activity['nama_kegiatan']);
                }
                $schedule ??= new Jadwal;
                $schedule->fill([
                    'jenis_pekerjaan_id' => $job->id,
                    'nama_kegiatan' => $activity['nama_kegiatan'],
                    'deskripsi' => $job->deskripsi,
                    'lokasi' => $activity['lokasi'],
                    'tanggal' => $activity['tanggal'],
                    'tanggal_selesai' => $activity['tanggal_selesai'],
                    'waktu_mulai' => $activity['waktu_mulai'],
                    'waktu_selesai' => $activity['waktu_selesai'],
                    'jumlah_orang_dibutuhkan' => count($activity['anggota']),
                    'jumlah_satuan' => 1,
                    'total_upah' => 180000 * count($activity['anggota']),
                    'perlu_verifikasi' => true,
                    'metode_penjadwalan' => 'manual',
                    'status' => $activity['status'],
                    'created_by' => $admin->id,
                ])->save();

                foreach ($activity['anggota'] as $memberNumber) {
                    $member = $members->get($memberNumber);
                    if ($member === null) {
                        throw new RuntimeException('Anggota simulasi tidak ditemukan: '.$memberNumber);
                    }

                    $assignmentData = $this->assignmentData($activity);
                    $assignment = Penugasan::query()->firstOrNew([
                        'jadwal_id' => $schedule->id,
                        'anggota_id' => $member->id,
                    ]);
                    $assignment->fill([
                        'original_anggota_id' => $member->id,
                        'tanggal_mulai' => $activity['tanggal'],
                        'tanggal_selesai' => $activity['tanggal_selesai'],
                        'status' => $assignmentData['status'],
                        'sumber_penugasan' => 'manual',
                        'assigned_at' => $activity['tanggal'].' 00:00:00',
                        'started_at' => $assignmentData['started_at'],
                        'completed_at' => $assignmentData['completed_at'],
                        'catatan_penyelesaian' => $assignmentData['catatan_penyelesaian'],
                        'verified_by' => $assignmentData['verified_by'] ? $admin->id : null,
                        'verified_at' => $assignmentData['verified_at'],
                        'catatan_verifikasi' => $assignmentData['catatan_verifikasi'],
                    ])->save();

                    $this->seedAttendance($assignment, $activity, $assignmentData['status_presensi']);

                    if ($activity['status'] === 'completed') {
                        app(UpahTugasService::class)->distributeForCompletedSchedule($schedule);
                    }
                }
            }
        });
    }

    private function seedCompetencies($members, $jobs): void
    {
        $slot = 0;
        foreach ($members as $member) {
            foreach ($jobs as $job) {
                $level = $this->competencyLevel($slot++);
                $competency = KompetensiAnggota::query()
                    ->where('anggota_id', $member->id)
                    ->where('jenis_pekerjaan_id', $job->id)
                    ->first();

                if ($competency && $competency->tingkat_kemampuan !== $level) {
                    throw new RuntimeException('Kompetensi anggota simulasi sudah berisi nilai berbeda; data yang ada tidak diubah.');
                }
                if (! $competency) {
                    KompetensiAnggota::query()->create([
                        'anggota_id' => $member->id,
                        'jenis_pekerjaan_id' => $job->id,
                        'tingkat_kemampuan' => $level,
                    ]);
                }
            }
        }
    }

    private function competencyLevel(int $slot): string
    {
        $bucket = ($slot * 73 + self::SEED) % 125;

        return match (true) {
            $bucket < 38 => 'pemula',
            $bucket < 94 => 'menengah',
            default => 'mahir',
        };
    }

    /**
     * @return array{status: string, started_at: ?string, completed_at: ?string, catatan_penyelesaian: ?string, verified_by: bool, verified_at: ?string, catatan_verifikasi: ?string, status_presensi: ?string}
     */
    private function assignmentData(array $activity): array
    {
        $isCompleted = $activity['status'] === 'completed';
        $status = $activity['status_penugasan']
            ?? ($isCompleted ? 'completed' : ($activity['status'] === 'ongoing' ? 'waiting_verification' : 'assigned'));
        $startAt = $activity['waktu_mulai'] === null ? null : $activity['tanggal'].' '.$activity['waktu_mulai'].':00';
        $endAt = $activity['waktu_selesai'] === null ? null : $activity['tanggal_selesai'].' '.$activity['waktu_selesai'].':00';
        $isReported = in_array($status, ['completed', 'waiting_verification'], true);

        return [
            'status' => $status,
            'started_at' => $isReported ? $startAt : null,
            'completed_at' => $isReported ? $endAt : null,
            'catatan_penyelesaian' => $isReported ? 'Kegiatan simulasi dilaporkan selesai.' : null,
            'verified_by' => $status === 'completed',
            'verified_at' => $status === 'completed' ? $endAt : null,
            'catatan_verifikasi' => $status === 'completed' ? 'Laporan simulasi disetujui.' : null,
            'status_presensi' => $activity['status_presensi'] ?? ($isCompleted ? 'hadir' : null),
        ];
    }

    private function seedAttendance(Penugasan $assignment, array $activity, ?string $status): void
    {
        if ($status === null) {
            Presensi::query()->where('penugasan_id', $assignment->id)->delete();

            return;
        }

        $start = Carbon::parse($activity['tanggal'])->startOfDay();
        $end = Carbon::parse($activity['tanggal_selesai'])->startOfDay();
        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dateString = $date->toDateString();
            $checkIn = $status === 'hadir' && $activity['waktu_mulai'] !== null
                ? $dateString.' '.$activity['waktu_mulai'].':00'
                : null;
            $checkOut = $status === 'hadir' && $activity['waktu_selesai'] !== null
                ? $dateString.' '.$activity['waktu_selesai'].':00'
                : null;

            $presence = Presensi::query()
                ->where('penugasan_id', $assignment->id)
                ->whereDate('tanggal', $dateString)
                ->first() ?? new Presensi;
            $presence->fill([
                'penugasan_id' => $assignment->id,
                'tanggal' => $dateString,
                'waktu_check_in' => $checkIn,
                'waktu_check_out' => $checkOut,
                'status' => $status,
                'catatan' => 'Presensi simulasi; bukan data lapangan.',
            ])->save();
        }
    }

    /**
     * @return list<array{nama_kegiatan: string, jenis_pekerjaan: string, lokasi: string, tanggal: string, tanggal_selesai: string, waktu_mulai: ?string, waktu_selesai: ?string, anggota: list<string>, status: string, status_penugasan: ?string, status_presensi: ?string}>
     */
    private function activities(): array
    {
        $timeSlots = [
            ['06:00', '08:00'],
            ['09:00', '11:30'],
            ['13:00', '16:00'],
        ];
        $activities = [];

        for ($day = 2; $day <= 15; $day++) {
            $date = Carbon::create(2026, 11, $day)->toDateString();
            for ($slot = 0; $slot < 3; $slot++) {
                $memberCount = 3 + (($day + $slot) % 3);
                $times = match (true) {
                    $day === 4 && $slot === 1,
                    $day === 11 && $slot === 2 => [null, null],
                    default => $timeSlots[$slot],
                };
                $isCompleted = $day <= 5;
                $isOngoing = $day > 5 && $day <= 8;
                $assignmentStatus = $day === 9 && $slot === 2
                    ? 'cancelled'
                    : ($day === 7 && $slot === 1 ? 'waiting_verification' : null);
                $attendanceStatus = match (true) {
                    $day === 8 && $slot === 2 => 'izin',
                    $day === 9 && $slot === 2 => 'tidak_hadir',
                    default => null,
                };

                $activities[] = [
                    'nama_kegiatan' => sprintf('SIMULASI SMART-ROC %s #%d', $date, $slot + 1),
                    'jenis_pekerjaan' => self::WORK_TYPES[($day + $slot + self::SEED) % count(self::WORK_TYPES)],
                    'lokasi' => sprintf('Lokasi simulasi %02d', (($day + $slot) % 5) + 1),
                    'tanggal' => $date,
                    'tanggal_selesai' => $date,
                    'waktu_mulai' => $times[0],
                    'waktu_selesai' => $times[1],
                    'anggota' => $this->memberNumbersFor($day * 3 + $slot, $memberCount),
                    'status' => $isCompleted ? 'completed' : ($isOngoing ? 'ongoing' : 'scheduled'),
                    'status_penugasan' => $assignmentStatus,
                    'status_presensi' => $attendanceStatus,
                ];
            }
        }

        foreach ([
            ['2026-11-05', '2026-11-06', 'Panen', 4, 'completed', 'hadir'],
            ['2026-11-11', '2026-11-13', 'Pembersihan Lahan', 7, 'scheduled', null],
        ] as [$start, $end, $workType, $memberSeed, $status, $attendance]) {
            $activities[] = [
                'nama_kegiatan' => 'SIMULASI SMART-ROC MULTI '.$start,
                'jenis_pekerjaan' => $workType,
                'lokasi' => 'Lokasi multi-hari simulasi',
                'tanggal' => $start,
                'tanggal_selesai' => $end,
                'waktu_mulai' => '16:00',
                'waktu_selesai' => '18:00',
                'anggota' => $this->memberNumbersFor($memberSeed, 5),
                'status' => $status,
                'status_penugasan' => null,
                'status_presensi' => $attendance,
            ];
        }

        return $activities;
    }

    /**
     * @return list<string>
     */
    private function memberNumbersFor(int $offset, int $count): array
    {
        $members = self::MEMBER_NUMBERS;
        $start = ($offset * 7 + self::SEED) % count($members);
        $selected = [];

        for ($index = 0; $index < $count; $index++) {
            $selected[] = $members[($start + $index * 3) % count($members)];
        }

        return $selected;
    }
}
