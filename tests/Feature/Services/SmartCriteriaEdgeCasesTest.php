<?php

namespace Tests\Feature\Services;

use App\Models\Jadwal;
use App\Services\SmartCriteriaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class SmartCriteriaEdgeCasesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('jadwal', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jenis_pekerjaan_id');
            $table->date('tanggal');
            $table->date('tanggal_selesai')->nullable();
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
        });
        Schema::create('penugasan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jadwal_id');
            $table->unsignedBigInteger('anggota_id');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status');
        });
        Schema::create('presensi', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('penugasan_id');
            $table->date('tanggal');
            $table->string('status');
        });
        Schema::create('kompetensi_anggota', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('anggota_id');
            $table->unsignedBigInteger('jenis_pekerjaan_id');
            $table->string('tingkat_kemampuan');
        });
    }

    public function test_future_assignments_in_the_load_window_count_but_target_schedule_does_not(): void
    {
        $target = $this->schedule('2026-04-15');
        $future = $this->schedule('2026-05-15');
        $this->assignment($target, 11, 'assigned', null, null);
        $this->assignment($future, 11, 'assigned', null, null);

        $criteria = app(SmartCriteriaService::class)->forCandidates($target, [11])[11];

        self::assertSame(4.0, $criteria['beban']);
        self::assertSame(30, $criteria['jeda']);
    }

    public function test_rest_gap_is_capped_at_the_configured_maximum(): void
    {
        $target = $this->schedule('2026-04-15');
        $oldSchedule = $this->schedule('2026-02-01');
        $this->assignment($oldSchedule, 12, 'completed', null, null);

        $criteria = app(SmartCriteriaService::class)->forCandidates($target, [12])[12];

        self::assertSame(30, $criteria['jeda']);
    }

    public function test_attendance_uses_the_90_day_start_boundary_and_excludes_target_date(): void
    {
        $target = $this->schedule('2026-04-15');
        $firstDay = $this->schedule('2026-01-15');
        $previousDay = $this->schedule('2026-04-14');
        $this->attendance($this->assignment($firstDay, 21, 'completed', null, null), '2026-01-15', 'hadir');
        $this->attendance($this->assignment($previousDay, 21, 'completed', null, null), '2026-04-14', 'tidak_hadir');
        $this->attendance($this->assignment($target, 21, 'completed', null, null), '2026-04-15', 'tidak_hadir');

        $criteria = app(SmartCriteriaService::class)->forCandidates($target, [21])[21];

        self::assertSame(0.5, $criteria['kehadiran']);
    }

    public function test_orphaned_assignment_is_reported_instead_of_silently_ignored(): void
    {
        $target = $this->schedule('2026-04-15');
        DB::table('penugasan')->insert([
            'jadwal_id' => 999,
            'anggota_id' => 31,
            'status' => 'assigned',
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(SmartCriteriaService::class)->forCandidates($target, [31]);
    }

    public function test_unknown_competency_value_is_reported(): void
    {
        $target = $this->schedule('2026-04-15');
        DB::table('kompetensi_anggota')->insert([
            'anggota_id' => 41,
            'jenis_pekerjaan_id' => 1,
            'tingkat_kemampuan' => 'ahli',
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(SmartCriteriaService::class)->forCandidates($target, [41]);
    }

    public function test_reversed_target_schedule_range_is_reported(): void
    {
        $target = $this->schedule('2026-04-15', '2026-04-14');

        $this->expectException(InvalidArgumentException::class);
        app(SmartCriteriaService::class)->forCandidates($target, [51]);
    }

    private function schedule(string $start, ?string $end = null): Jadwal
    {
        $id = DB::table('jadwal')->insertGetId([
            'jenis_pekerjaan_id' => 1,
            'tanggal' => $start,
            'tanggal_selesai' => $end,
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '12:00:00',
        ]);

        return Jadwal::query()->findOrFail($id);
    }

    private function assignment(Jadwal $schedule, int $memberId, string $status, ?string $start, ?string $end): int
    {
        return (int) DB::table('penugasan')->insertGetId([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $memberId,
            'tanggal_mulai' => $start,
            'tanggal_selesai' => $end,
            'status' => $status,
        ]);
    }

    private function attendance(int $assignmentId, string $date, string $status): void
    {
        DB::table('presensi')->insert([
            'penugasan_id' => $assignmentId,
            'tanggal' => $date,
            'status' => $status,
        ]);
    }
}
