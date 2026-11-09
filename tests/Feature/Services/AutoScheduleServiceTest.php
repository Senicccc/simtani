<?php

namespace Tests\Feature\Services;

use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\User;
use App\Services\AutoScheduleService;
use App\Services\JadwalStatusService;
use App\Services\ScheduleConflictService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutoScheduleServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_anggota')->unique();
            $table->string('nama_lengkap');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role');
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
        Schema::create('jenis_pekerjaan', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_pekerjaan');
            $table->timestamps();
        });
        Schema::create('jadwal', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jenis_pekerjaan_id');
            $table->string('nama_kegiatan');
            $table->date('tanggal');
            $table->date('tanggal_selesai')->nullable();
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->unsignedInteger('jumlah_orang_dibutuhkan');
            $table->string('metode_penjadwalan');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('penugasan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jadwal_id');
            $table->unsignedBigInteger('anggota_id');
            $table->unsignedBigInteger('original_anggota_id')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status');
            $table->string('sumber_penugasan');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->text('catatan_perubahan')->nullable();
            $table->timestamps();
        });
        Schema::create('presensi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penugasan_id');
            $table->date('tanggal');
            $table->string('status');
            $table->boolean('coverage_accepted')->default(false);
        });
        Schema::create('kompetensi_anggota', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('anggota_id');
            $table->unsignedBigInteger('jenis_pekerjaan_id');
            $table->string('tingkat_kemampuan');
        });
        Schema::create('rekomendasi_penugasan_log', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jadwal_id');
            $table->unsignedBigInteger('anggota_id');
            $table->unsignedBigInteger('penugasan_id')->nullable();
            $table->string('algoritma');
            $table->unsignedInteger('peringkat');
            $table->decimal('skor', 8, 6);
            $table->json('rincian');
            $table->boolean('dipilih');
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function test_preview_does_not_write_logs_and_recommendations_do_not_exceed_slots(): void
    {
        $schedule = $this->schedule(2);
        $this->members(4);
        $service = app(AutoScheduleService::class);
        $conflicts = app(ScheduleConflictService::class);

        $preview = $service->recommendationsForSchedule($schedule, $conflicts);

        self::assertSame(2, $preview['vacancies']);
        self::assertSame(2, $preview['recommended_count']);
        self::assertLessThanOrEqual($preview['required_slots'], $preview['recommended_count']);
        self::assertCount(4, $preview['candidates']);
        self::assertSame(0, DB::table('rekomendasi_penugasan_log')->count());
    }

    public function test_smart_details_preserve_legacy_keys_and_match_new_criteria_structure(): void
    {
        $schedule = $this->schedule(1);
        $this->members(2);
        $service = app(AutoScheduleService::class);
        $candidate = $service->recommendationsForSchedule(
            $schedule,
            app(ScheduleConflictService::class),
        )['candidates'][0];
        $details = $candidate['rincian'];

        foreach (['beban_jam', 'jeda_hari', 'kompetensi_level', 'kehadiran', 'u1', 'u2', 'u3', 'u4'] as $key) {
            self::assertArrayHasKey($key, $details);
        }
        foreach (['kriteria', 'urutan_kriteria', 'parameter', 'skor', 'skor_format_presisi_penuh'] as $key) {
            self::assertArrayHasKey($key, $details);
        }

        self::assertSame(['beban', 'jeda', 'kompetensi', 'kehadiran'], $details['urutan_kriteria']);
        self::assertSame($details['beban_jam'], $details['kriteria']['beban']['nilai_mentah']);
        self::assertSame($details['jeda_hari'], $details['kriteria']['jeda']['nilai_mentah']);
        self::assertSame($details['kompetensi_level'], $details['kriteria']['kompetensi']['nilai_mentah']);
        self::assertSame($details['kehadiran'], $details['kriteria']['kehadiran']['nilai_mentah']);
        self::assertSame($details['u1'], $details['kriteria']['beban']['utilitas']);
        self::assertSame($details['u2'], $details['kriteria']['jeda']['utilitas']);
        self::assertSame($details['u3'], $details['kriteria']['kompetensi']['utilitas']);
        self::assertSame($details['u4'], $details['kriteria']['kehadiran']['utilitas']);
        self::assertSame($candidate['skor'], $details['skor']);
        self::assertSame(
            $candidate['skor'],
            (float) $details['skor_format_presisi_penuh'],
        );
        self::assertStringStartsWith('belum ada kompetensi yang tercatat', $candidate['reason']);
        self::assertStringContainsString(' · Skor SMART ', $candidate['reason']);
        self::assertStringNotContainsString('Ã', $candidate['reason']);
        self::assertStringNotContainsString('â', $candidate['reason']);
    }

    public function test_multi_day_recommendation_can_overstaff_a_day_when_daily_coverage_differs(): void
    {
        $schedule = $this->schedule(1, '2026-11-11');
        $members = $this->members(3);
        Penugasan::query()->create([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $members[0]->id,
            'original_anggota_id' => $members[0]->id,
            'tanggal_mulai' => '2026-11-10',
            'tanggal_selesai' => '2026-11-10',
            'status' => 'assigned',
            'sumber_penugasan' => 'manual',
            'assigned_at' => now(),
        ]);

        $service = app(AutoScheduleService::class);
        $conflicts = app(ScheduleConflictService::class);
        $preview = $service->recommendationsForSchedule($schedule, $conflicts);

        self::assertSame(0, $preview['assigned_count']);
        self::assertSame(1, $preview['vacancies']);
        self::assertSame(1, $preview['recommended_count']);

        $service->generateForSchedule($schedule, $conflicts, $preview['recommended_member_ids']);
        $coverage = app(JadwalStatusService::class)->dailyCoverage($schedule);

        self::assertSame([2, 1], $coverage->pluck('anggota')->all());
        self::assertSame([1, 1], $coverage->pluck('kebutuhan')->all());
        self::assertGreaterThan($coverage[0]['kebutuhan'], $coverage[0]['anggota']);
    }

    public function test_candidate_filters_preserve_cancelled_reassignment_and_exclude_unavailable_members(): void
    {
        $schedule = $this->schedule(2);
        $members = $this->members(6);
        DB::table('users')->where('id', $members[0]->id)->update(['status_aktif' => false]);
        DB::table('users')->where('id', $members[1]->id)->update(['role' => 'admin']);

        $conflictingSchedule = $this->schedule(1);
        Penugasan::query()->create([
            'jadwal_id' => $conflictingSchedule->id,
            'anggota_id' => $members[2]->id,
            'original_anggota_id' => $members[2]->id,
            'status' => 'assigned',
            'sumber_penugasan' => 'manual',
            'assigned_at' => now(),
        ]);
        Penugasan::query()->create([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $members[3]->id,
            'original_anggota_id' => $members[3]->id,
            'status' => 'assigned',
            'sumber_penugasan' => 'manual',
            'assigned_at' => now(),
        ]);
        Penugasan::query()->create([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $members[4]->id,
            'original_anggota_id' => $members[4]->id,
            'status' => 'cancelled',
            'sumber_penugasan' => 'manual',
            'assigned_at' => now(),
        ]);

        $preview = app(AutoScheduleService::class)->recommendationsForSchedule(
            $schedule,
            app(ScheduleConflictService::class),
        );

        self::assertSame([$members[4]->id, $members[5]->id], array_column($preview['candidates'], 'anggota_id'));
    }

    public function test_generate_links_selected_candidates_and_logs_unselected_candidates(): void
    {
        $schedule = $this->schedule(1);
        $members = $this->members(3);
        $service = app(AutoScheduleService::class);
        $conflicts = app(ScheduleConflictService::class);
        $preview = $service->recommendationsForSchedule($schedule, $conflicts);
        $selectedId = $members[1]->id;

        $created = $service->generateForSchedule($schedule, $conflicts, [$selectedId]);

        self::assertSame([$selectedId], array_column($created, 'anggota_id'));
        self::assertSame(1, Penugasan::query()->where('jadwal_id', $schedule->id)->count());
        self::assertSame(3, DB::table('rekomendasi_penugasan_log')->where('jadwal_id', $schedule->id)->count());

        $selectedLog = DB::table('rekomendasi_penugasan_log')
            ->where('anggota_id', $selectedId)
            ->first();
        self::assertSame(1, (int) $selectedLog->dipilih);
        self::assertNotNull($selectedLog->penugasan_id);
        self::assertSame(
            (int) Penugasan::query()->where('jadwal_id', $schedule->id)->where('anggota_id', $selectedId)->value('id'),
            (int) $selectedLog->penugasan_id,
        );

        $unselectedLogs = DB::table('rekomendasi_penugasan_log')
            ->where('jadwal_id', $schedule->id)
            ->where('anggota_id', '!=', $selectedId)
            ->get();
        self::assertCount(2, $unselectedLogs);
        foreach ($unselectedLogs as $log) {
            self::assertSame(0, (int) $log->dipilih);
            self::assertNull($log->penugasan_id);
        }

        self::assertSame(1, $preview['recommended_count']);
    }

    public function test_generate_again_does_not_duplicate_assignments_or_logs(): void
    {
        $schedule = $this->schedule(1);
        $members = $this->members(2);
        $service = app(AutoScheduleService::class);
        $conflicts = app(ScheduleConflictService::class);

        $service->generateForSchedule($schedule, $conflicts, [$members[0]->id]);
        $firstLogCount = DB::table('rekomendasi_penugasan_log')->count();
        $secondResult = $service->generateForSchedule($schedule, $conflicts);

        self::assertSame([], $secondResult);
        self::assertSame(1, Penugasan::query()->where('jadwal_id', $schedule->id)->count());
        self::assertSame($firstLogCount, DB::table('rekomendasi_penugasan_log')->count());
    }

    public function test_legacy_mode_keeps_legacy_candidate_shape_and_records_legacy_algorithm(): void
    {
        config(['smart.algoritma' => 'legacy']);
        $schedule = $this->schedule(1);
        $members = $this->members(3);
        $service = app(AutoScheduleService::class);
        $conflicts = app(ScheduleConflictService::class);

        $preview = $service->recommendationsForSchedule($schedule, $conflicts);

        self::assertSame([$members[0]->id], $preview['recommended_member_ids']);
        self::assertArrayNotHasKey('skor', $preview['candidates'][0]);

        $service->generateForSchedule($schedule, $conflicts, [$members[0]->id]);

        self::assertSame(
            ['legacy'],
            DB::table('rekomendasi_penugasan_log')->distinct()->pluck('algoritma')->all(),
        );
    }

    private function schedule(int $requiredSlots, ?string $endDate = null): Jadwal
    {
        $workTypeId = DB::table('jenis_pekerjaan')->insertGetId([
            'nama_pekerjaan' => 'Panen',
        ]);
        $scheduleId = DB::table('jadwal')->insertGetId([
            'jenis_pekerjaan_id' => $workTypeId,
            'nama_kegiatan' => 'Panen uji',
            'tanggal' => '2026-11-10',
            'tanggal_selesai' => $endDate ?? '2026-11-10',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '12:00:00',
            'jumlah_orang_dibutuhkan' => $requiredSlots,
            'metode_penjadwalan' => 'otomatis',
            'status' => 'scheduled',
        ]);

        return Jadwal::query()->findOrFail($scheduleId);
    }

    /**
     * @return array<int, User>
     */
    private function members(int $count): array
    {
        $members = [];
        for ($index = 1; $index <= $count; $index++) {
            $memberId = DB::table('users')->insertGetId([
                'nomor_anggota' => sprintf('TEST-%03d', $index),
                'nama_lengkap' => sprintf('Anggota %03d', $index),
                'email' => sprintf('member-%d@example.test', $index),
                'role' => 'anggota',
                'status_aktif' => true,
            ]);
            $members[] = User::query()->findOrFail($memberId);
        }

        return $members;
    }
}
