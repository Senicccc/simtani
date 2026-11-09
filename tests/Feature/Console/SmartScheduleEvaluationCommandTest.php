<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SmartScheduleEvaluationCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_anggota');
            $table->string('nama_lengkap');
            $table->string('role');
            $table->boolean('status_aktif');
        });
        Schema::create('jadwal', function (Blueprint $table): void {
            $table->id();
            $table->date('tanggal');
            $table->date('tanggal_selesai')->nullable();
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->unsignedBigInteger('jenis_pekerjaan_id');
        });
        Schema::create('penugasan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('jadwal_id');
            $table->unsignedBigInteger('anggota_id');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status');
            $table->string('sumber_penugasan');
        });
        Schema::create('kompetensi_anggota', function (Blueprint $table): void {
            $table->unsignedBigInteger('anggota_id');
            $table->unsignedBigInteger('jenis_pekerjaan_id');
            $table->string('tingkat_kemampuan');
        });
        Schema::create('permintaan_perubahan', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('diajukan_oleh');
            $table->dateTime('created_at');
            $table->string('jenis_permintaan');
        });
    }

    public function test_evaluation_hours_match_c1_statuses_effective_dates_and_inclusive_overlap(): void
    {
        $this->member(1, 'TEST-001', 'Aktif satu');
        $this->member(2, 'TEST-002', 'Aktif dua');
        $this->member(3, 'TEST-003', 'Nonaktif');
        DB::table('users')->where('id', 3)->update(['status_aktif' => false]);

        $this->assignment(1, 1, 'assigned', '2026-11-02', '2026-11-04', 'manual', '08:00:00', '12:00:00');
        $this->assignment(2, 1, 'in_progress', '2026-11-01', '2026-11-03', 'otomatis', '08:00:00', '10:00:00');
        $this->assignment(3, 1, 'waiting_verification', '2026-11-04', '2026-11-05', 'hasil_perubahan', null, null);
        $this->assignment(4, 1, 'completed', '2026-11-05', '2026-11-05', 'manual', '08:00:00', '09:30:00');
        $this->assignment(5, 1, 'rejected', '2026-11-02', '2026-11-04', 'otomatis', '08:00:00', '12:00:00');
        $this->assignment(6, 1, 'cancelled', '2026-11-02', '2026-11-04', 'manual', '08:00:00', '12:00:00');
        $this->assignment(7, 1, 'completed', '2026-10-25', '2026-11-01', 'manual', '08:00:00', '12:00:00');
        $this->assignment(8, 1, 'completed', '2026-11-10', '2026-11-11', 'manual', '08:00:00', '12:00:00');
        $this->assignment(9, 2, 'assigned', '2026-11-03', '2026-11-04', 'manual', '08:00:00', '12:00:00');

        $exitCode = Artisan::call('smart:evaluasi', ['--dari' => '2026-11-02', '--sampai' => '2026-11-05']);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Aktif satu', $output);
        self::assertStringContainsString('29,50', $output, $output);
        self::assertStringContainsString('8,00', $output);
        self::assertStringNotContainsString('Nonaktif', $output);
        self::assertStringContainsString(
            'Jumlah record penugasan non-cancelled per sumber (semua status selain cancelled; bukan jam C1)',
            $output,
        );
        self::assertStringContainsString('{"otomatis":2,"manual":3,"hasil_perubahan":1}', $output);
    }

    public function test_zero_hour_population_reports_zero_gini_and_undefined_ratio_as_na(): void
    {
        $this->member(1, 'TEST-001', 'Anggota tanpa tugas');

        $exitCode = Artisan::call('smart:evaluasi', ['--dari' => '2026-11-02', '--sampai' => '2026-11-05']);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('0,00', $output);
        self::assertStringContainsString('Gini: 0,0000', $output);
        self::assertStringContainsString('Rasio maksimum/rata-rata: N/A', $output);
        self::assertStringContainsString('Rata-rata level kompetensi per penugasan: N/A', $output);
    }

    public function test_empty_active_member_population_is_reported_without_division_by_zero(): void
    {
        $exitCode = Artisan::call('smart:evaluasi', ['--dari' => '2026-11-02', '--sampai' => '2026-11-05']);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('SD jam populasi: 0,0000', $output);
        self::assertStringContainsString('Gini: 0,0000', $output);
        self::assertStringContainsString('Rasio maksimum/rata-rata: N/A', $output);
    }

    public function test_period_report_includes_partial_assignment_dates_and_writes_utf8_csv_headers(): void
    {
        $this->member(1, 'TEST-001', 'Anggota Uji');
        $csvPath = tempnam(sys_get_temp_dir(), 'simtani-evaluasi-');
        self::assertNotFalse($csvPath);

        DB::table('jadwal')->insert([
            'id' => 1,
            'tanggal' => '2026-11-20',
            'tanggal_selesai' => '2026-11-20',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '12:00:00',
            'jenis_pekerjaan_id' => 1,
        ]);
        DB::table('penugasan')->insert([
            'jadwal_id' => 1,
            'anggota_id' => 1,
            'tanggal_mulai' => '2026-11-04',
            'tanggal_selesai' => '2026-11-05',
            'status' => 'assigned',
            'sumber_penugasan' => 'otomatis',
        ]);

        try {
            $exitCode = Artisan::call('smart:evaluasi', [
                '--dari' => '2026-11-02',
                '--sampai' => '2026-11-05',
                '--csv' => $csvPath,
            ]);
            $output = Artisan::output();
            $csv = file_get_contents($csvPath);

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('8,00', $output);
            self::assertStringContainsString('{"otomatis":1,"manual":0,"hasil_perubahan":0}', $output);
            self::assertIsString($csv);
            self::assertStringStartsWith("\xEF\xBB\xBFnomor_anggota,nama_lengkap,total_jam_C1", $csv);
            self::assertStringContainsString('Anggota Uji', $csv);
        } finally {
            unlink($csvPath);
        }
    }

    private function member(int $id, string $number, string $name): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'nomor_anggota' => $number,
            'nama_lengkap' => $name,
            'role' => 'anggota',
            'status_aktif' => true,
        ]);
    }

    private function assignment(
        int $scheduleId,
        int $memberId,
        string $status,
        string $assignmentStart,
        string $assignmentEnd,
        string $source,
        ?string $startTime,
        ?string $endTime,
    ): void {
        DB::table('jadwal')->insert([
            'id' => $scheduleId,
            'tanggal' => $assignmentStart,
            'tanggal_selesai' => $assignmentEnd,
            'waktu_mulai' => $startTime,
            'waktu_selesai' => $endTime,
            'jenis_pekerjaan_id' => 1,
        ]);
        DB::table('penugasan')->insert([
            'jadwal_id' => $scheduleId,
            'anggota_id' => $memberId,
            'tanggal_mulai' => $assignmentStart,
            'tanggal_selesai' => $assignmentEnd,
            'status' => $status,
            'sumber_penugasan' => $source,
        ]);
    }
}
