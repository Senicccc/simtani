<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\Penugasan;
use App\Models\Presensi;
use App\Models\User;
use App\Services\AutoScheduleService;
use App\Services\ScheduleConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoScheduleSmartTest extends TestCase
{
    use RefreshDatabase;

    public function test_cold_start_recommendations_keep_old_fields_and_explain_smart_scores(): void
    {
        $admin = $this->user('admin');
        $job = $this->job($admin);
        $schedule = $this->schedule($admin, $job);
        $members = [$this->user(), $this->user(), $this->user()];

        $recommendations = app(AutoScheduleService::class)
            ->recommendationsForSchedule($schedule, app(ScheduleConflictService::class));

        $this->assertCount(3, $recommendations['candidates']);
        foreach ($recommendations['candidates'] as $candidate) {
            foreach ([
                'anggota_id',
                'nama_lengkap',
                'nomor_anggota',
                'tingkat_kemampuan',
                'jumlah_tugas_aktif',
                'jumlah_tugas_selesai',
                'reason',
                'skor',
                'peringkat',
                'rincian',
            ] as $key) {
                $this->assertArrayHasKey($key, $candidate);
            }

            $this->assertStringContainsString('tugas aktif', $candidate['reason']);
            $this->assertSame(0.0, $candidate['rincian']['beban_jam']);
            $this->assertSame(30, $candidate['rincian']['jeda_hari']);
            $this->assertSame(1.0, $candidate['rincian']['kehadiran']);
        }

        $this->assertSame(
            $members[0]->id,
            $recommendations['candidates'][0]['anggota_id'],
        );
    }

    public function test_more_work_hours_result_in_a_lower_recommendation_rank(): void
    {
        $admin = $this->user('admin');
        $job = $this->job($admin);
        $schedule = $this->schedule($admin, $job, ['tanggal' => '2026-11-10', 'tanggal_selesai' => '2026-11-10']);
        $lightlyLoaded = $this->user();
        $heavilyLoaded = $this->user();

        $this->assignment($this->schedule($admin, $job, [
            'tanggal' => '2026-11-01',
            'tanggal_selesai' => '2026-11-01',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:00',
        ]), $lightlyLoaded, 'completed');
        $this->assignment($this->schedule($admin, $job, [
            'tanggal' => '2026-11-01',
            'tanggal_selesai' => '2026-11-01',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '16:00',
        ]), $heavilyLoaded, 'completed');

        $candidates = app(AutoScheduleService::class)
            ->manualRecommendationsForSchedule($schedule, app(ScheduleConflictService::class));

        $this->assertSame($lightlyLoaded->id, $candidates[0]['anggota_id']);
        $this->assertSame($heavilyLoaded->id, $candidates[1]['anggota_id']);
    }

    public function test_multi_day_assignments_count_each_overlapping_day(): void
    {
        $admin = $this->user('admin');
        $job = $this->job($admin);
        $target = $this->schedule($admin, $job, ['tanggal' => '2026-11-10', 'tanggal_selesai' => '2026-11-10']);
        $member = $this->user();
        $previousSchedule = $this->schedule($admin, $job, [
            'tanggal' => '2026-10-20',
            'tanggal_selesai' => '2026-10-22',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '12:00',
        ]);
        $this->assignment($previousSchedule, $member, 'completed');

        $candidates = app(AutoScheduleService::class)
            ->manualRecommendationsForSchedule($target, app(ScheduleConflictService::class));

        $this->assertSame(12.0, $candidates[0]['rincian']['beban_jam']);
    }

    public function test_missing_times_use_the_configured_default_and_cancelled_work_is_ignored(): void
    {
        config(['smart.jam_bawaan_per_hari' => 7]);
        $admin = $this->user('admin');
        $job = $this->job($admin);
        $target = $this->schedule($admin, $job, ['tanggal' => '2026-11-10', 'tanggal_selesai' => '2026-11-10']);
        $member = $this->user();
        $untimedSchedule = $this->schedule($admin, $job, [
            'tanggal' => '2026-11-01',
            'tanggal_selesai' => '2026-11-01',
            'waktu_mulai' => null,
            'waktu_selesai' => null,
        ]);
        $this->assignment($untimedSchedule, $member, 'completed');
        $cancelledSchedule = $this->schedule($admin, $job, [
            'tanggal' => '2026-11-02',
            'tanggal_selesai' => '2026-11-02',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '16:00',
        ]);
        $this->assignment($cancelledSchedule, $member, 'cancelled');

        $candidates = app(AutoScheduleService::class)
            ->manualRecommendationsForSchedule($target, app(ScheduleConflictService::class));

        $this->assertSame(7.0, $candidates[0]['rincian']['beban_jam']);
    }

    public function test_attendance_excludes_leave_and_counts_absence(): void
    {
        $admin = $this->user('admin');
        $job = $this->job($admin);
        $target = $this->schedule($admin, $job, ['tanggal' => '2026-11-10', 'tanggal_selesai' => '2026-11-10']);
        $member = $this->user();

        foreach ([
            ['date' => '2026-11-01', 'status' => 'hadir'],
            ['date' => '2026-11-02', 'status' => 'izin'],
            ['date' => '2026-11-03', 'status' => 'tidak_hadir'],
        ] as $presenceData) {
            $pastSchedule = $this->schedule($admin, $job, [
                'tanggal' => $presenceData['date'],
                'tanggal_selesai' => $presenceData['date'],
            ]);
            $assignment = $this->assignment($pastSchedule, $member, 'completed');
            Presensi::create([
                'penugasan_id' => $assignment->id,
                'tanggal' => $presenceData['date'],
                'status' => $presenceData['status'],
            ]);
        }

        $candidates = app(AutoScheduleService::class)
            ->manualRecommendationsForSchedule($target, app(ScheduleConflictService::class));

        $this->assertSame(0.5, $candidates[0]['rincian']['kehadiran']);
    }

    public function test_inactive_conflicting_and_already_assigned_members_are_excluded(): void
    {
        $admin = $this->user('admin');
        $job = $this->job($admin);
        $target = $this->schedule($admin, $job, ['jumlah_orang_dibutuhkan' => 4]);
        $available = $this->user();
        $inactive = $this->user('anggota', false);
        $conflicted = $this->user();
        $alreadyAssigned = $this->user();

        $this->assignment($this->schedule($admin, $job), $conflicted);
        $this->assignment($target, $alreadyAssigned);

        $candidates = app(AutoScheduleService::class)
            ->manualRecommendationsForSchedule($target, app(ScheduleConflictService::class));
        $candidateIds = array_column($candidates, 'anggota_id');

        $this->assertSame([$available->id], $candidateIds);
        $this->assertNotContains($inactive->id, $candidateIds);
    }

    public function test_legacy_mode_preserves_the_original_order(): void
    {
        config(['smart.algoritma' => 'legacy']);
        $admin = $this->user('admin');
        $job = $this->job($admin);
        $target = $this->schedule($admin, $job, ['tanggal' => '2026-11-10', 'tanggal_selesai' => '2026-11-10']);
        $memberWithHistory = $this->user();
        $memberWithoutHistory = $this->user();
        $previousSchedule = $this->schedule($admin, $job, [
            'tanggal' => '2026-11-01',
            'tanggal_selesai' => '2026-11-01',
        ]);
        $this->assignment($previousSchedule, $memberWithHistory, 'completed');

        $candidates = app(AutoScheduleService::class)
            ->manualRecommendationsForSchedule($target, app(ScheduleConflictService::class));

        $this->assertSame($memberWithoutHistory->id, $candidates[0]['anggota_id']);
        $this->assertSame($memberWithHistory->id, $candidates[1]['anggota_id']);
    }

    public function test_generation_logs_every_candidate_but_previews_do_not(): void
    {
        $admin = $this->user('admin');
        $job = $this->job($admin);
        $target = $this->schedule($admin, $job, ['jumlah_orang_dibutuhkan' => 1]);
        $members = [$this->user(), $this->user()];
        $service = app(AutoScheduleService::class);
        $conflicts = app(ScheduleConflictService::class);
        $this->actingAs($admin);

        $this->getJson(route('admin.jadwal.auto-preview', $target))->assertOk();
        $service->recommendationsForSchedule($target, $conflicts);
        $this->assertDatabaseCount('rekomendasi_penugasan_log', 0);

        $selectedId = $service->recommendationsForSchedule($target, $conflicts)['recommended_member_ids'][0];
        $service->generateForSchedule($target, $conflicts, [$selectedId]);

        $this->assertDatabaseCount('rekomendasi_penugasan_log', 2);
        $this->assertDatabaseHas('rekomendasi_penugasan_log', [
            'jadwal_id' => $target->id,
            'anggota_id' => $selectedId,
            'algoritma' => 'smart',
            'dipilih' => true,
            'dibuat_oleh' => $admin->id,
        ]);
        $this->assertDatabaseHas('rekomendasi_penugasan_log', [
            'jadwal_id' => $target->id,
            'anggota_id' => $selectedId === $members[0]->id ? $members[1]->id : $members[0]->id,
            'dipilih' => false,
        ]);
    }

    private function user(string $role = 'anggota', bool $active = true): User
    {
        return User::factory()->create([
            'role' => $role,
            'status_aktif' => $active,
        ]);
    }

    private function job(User $admin): JenisPekerjaan
    {
        return JenisPekerjaan::create([
            'nama_pekerjaan' => 'Pekerjaan '.fake()->unique()->numerify('###'),
            'deskripsi' => 'Pekerjaan pertanian',
            'satuan' => 'petak',
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function schedule(User $admin, JenisPekerjaan $job, array $attributes = []): Jadwal
    {
        return Jadwal::create([
            'jenis_pekerjaan_id' => $job->id,
            'nama_kegiatan' => 'Kegiatan '.fake()->unique()->numerify('###'),
            'deskripsi' => 'Kegiatan uji',
            'lokasi' => 'Lahan uji',
            'tanggal' => '2026-11-02',
            'tanggal_selesai' => '2026-11-02',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '12:00',
            'jumlah_orang_dibutuhkan' => 1,
            'jumlah_satuan' => 1,
            'total_upah' => 150000,
            'perlu_verifikasi' => true,
            'metode_penjadwalan' => 'manual',
            'status' => 'scheduled',
            'created_by' => $admin->id,
            ...$attributes,
        ]);
    }

    private function assignment(Jadwal $schedule, User $member, string $status = 'assigned'): Penugasan
    {
        return Penugasan::create([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $member->id,
            'original_anggota_id' => $member->id,
            'status' => $status,
            'sumber_penugasan' => 'manual',
            'assigned_at' => now(),
        ]);
    }
}
