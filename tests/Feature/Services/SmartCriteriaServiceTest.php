<?php

namespace Tests\Feature\Services;

use App\Models\Jadwal;
use App\Services\SmartCriteriaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class SmartCriteriaServiceTest extends TestCase
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

    public function test_rejected_assignment_is_excluded_from_c1_but_informs_c2_and_c4(): void
    {
        $target = $this->schedule('2026-04-15', null, '08:00:00', '12:00:00', 1);
        $previousSchedule = $this->schedule('2026-04-10', null, '08:00:00', '12:00:00', 1);
        $firstRejected = $this->assignment($previousSchedule, 11, 'rejected', '2026-04-10', '2026-04-10');
        $secondRejected = $this->assignment($previousSchedule, 11, 'rejected', '2026-04-11', '2026-04-11');
        $this->attendance($firstRejected, '2026-04-10', 'tidak_hadir');
        $this->attendance($secondRejected, '2026-04-11', 'hadir');

        $criteria = app(SmartCriteriaService::class)->forCandidates($target, [11])[11];

        self::assertSame(0.0, $criteria['beban']);
        self::assertSame(4, $criteria['jeda']);
        self::assertSame(0.5, $criteria['kehadiran']);
    }

    public function test_c1_counts_only_the_four_baseline_statuses_and_counts_days_inclusively(): void
    {
        $target = $this->schedule('2026-04-15', '2026-04-16', '08:00:00', '12:00:00', 1);
        $history = $this->schedule('2026-03-26', '2026-03-28', '08:00:00', '12:00:00', 1);

        foreach (['assigned', 'in_progress', 'waiting_verification', 'completed'] as $index => $status) {
            $this->assignment($history, 20 + $index, $status, '2026-03-26', '2026-03-28');
        }
        $this->assignment($history, 30, 'rejected', '2026-03-26', '2026-03-28');
        $this->assignment($history, 31, 'cancelled', '2026-03-26', '2026-03-28');

        $workloads = app(SmartCriteriaService::class)->workloadsForPeriod(
            now()->parse('2026-03-26'),
            now()->parse('2026-03-28'),
            [20, 21, 22, 23, 30, 31],
        );

        self::assertSame([12.0, 12.0, 12.0, 12.0, 0.0, 0.0], array_values($workloads));
    }

    public function test_partial_assignment_dates_and_null_time_use_effective_range_and_default_hours(): void
    {
        $target = $this->schedule('2026-04-15', null, null, null, 1);
        $history = $this->schedule('2026-03-20', '2026-04-05', null, null, 1);
        $this->assignment($history, 41, 'assigned', '2026-03-25', '2026-03-27');

        $criteria = app(SmartCriteriaService::class)->forCandidates($target, [41])[41];

        self::assertSame(18.0, $criteria['beban']);
    }

    public function test_null_assignment_dates_inherit_schedule_and_decimal_hours_are_preserved(): void
    {
        $target = $this->schedule('2026-04-15', null, '08:00:00', '12:00:00', 1);
        $history = $this->schedule('2026-03-16', '2026-03-18', '08:00:00', '11:30:00', 1);
        $this->assignment($history, 45, 'assigned', null, null);

        $criteria = app(SmartCriteriaService::class)->forCandidates($target, [45])[45];

        self::assertSame(10.5, $criteria['beban']);
    }

    public function test_attendance_ignores_excused_records_and_defaults_to_full_attendance_when_empty(): void
    {
        $target = $this->schedule('2026-04-15', null, '08:00:00', '12:00:00', 1);
        $history = $this->schedule('2026-04-10', null, '08:00:00', '12:00:00', 1);
        $assignment = $this->assignment($history, 51, 'completed', '2026-04-10', '2026-04-10');
        $this->attendance($assignment, '2026-04-10', 'hadir');
        $this->attendance($assignment, '2026-04-11', 'izin');
        $this->attendance($assignment, '2026-04-12', 'tidak_hadir');

        $criteria = app(SmartCriteriaService::class)->forCandidates($target, [51, 52]);

        self::assertSame(0.5, $criteria[51]['kehadiran']);
        self::assertSame(1.0, $criteria[52]['kehadiran']);
    }

    public function test_invalid_assignment_range_is_reported_instead_of_counted_negatively(): void
    {
        $target = $this->schedule('2026-04-15', null, '08:00:00', '12:00:00', 1);
        $history = $this->schedule('2026-04-10', null, '08:00:00', '12:00:00', 1);
        $this->assignment($history, 61, 'assigned', '2026-04-12', '2026-04-10');

        $this->expectException(InvalidArgumentException::class);
        app(SmartCriteriaService::class)->forCandidates($target, [61]);
    }

    public function test_cancelled_assignment_is_excluded_from_c1_and_c2_but_its_presence_is_still_counted_in_c4(): void
    {
        $target = $this->schedule('2026-04-15', null, '08:00:00', '12:00:00', 1);
        $history = $this->schedule('2026-04-10', null, '08:00:00', '12:00:00', 1);
        $cancelled = $this->assignment($history, 71, 'cancelled', '2026-04-10', '2026-04-10');
        $this->attendance($cancelled, '2026-04-10', 'tidak_hadir');

        $criteria = app(SmartCriteriaService::class)->forCandidates($target, [71])[71];

        self::assertEquals(0.0, $criteria['beban']);
        self::assertSame(30, $criteria['jeda']);
        self::assertEquals(0.0, $criteria['kehadiran']);
    }

    public function test_competency_is_scoped_to_the_target_work_type_and_absence_defaults_to_level_one(): void
    {
        $target = $this->schedule('2026-04-15', null, '08:00:00', '12:00:00', 4);
        DB::table('kompetensi_anggota')->insert([
            ['anggota_id' => 81, 'jenis_pekerjaan_id' => 4, 'tingkat_kemampuan' => 'menengah'],
            ['anggota_id' => 81, 'jenis_pekerjaan_id' => 9, 'tingkat_kemampuan' => 'mahir'],
        ]);

        $criteria = app(SmartCriteriaService::class)->forCandidates($target, [81, 82]);

        self::assertSame(2, $criteria[81]['kompetensi']);
        self::assertSame(1, $criteria[82]['kompetensi']);
        self::assertSame(30, $criteria[82]['jeda']);
    }

    private function schedule(
        string $start,
        ?string $end,
        ?string $timeStart,
        ?string $timeEnd,
        int $workTypeId,
    ): Jadwal {
        $id = DB::table('jadwal')->insertGetId([
            'jenis_pekerjaan_id' => $workTypeId,
            'tanggal' => $start,
            'tanggal_selesai' => $end,
            'waktu_mulai' => $timeStart,
            'waktu_selesai' => $timeEnd,
        ]);

        return Jadwal::query()->findOrFail($id);
    }

    private function assignment(
        Jadwal $schedule,
        int $memberId,
        string $status,
        ?string $start,
        ?string $end,
    ): int {
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
