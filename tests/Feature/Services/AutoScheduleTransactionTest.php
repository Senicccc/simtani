<?php

namespace Tests\Feature\Services;

use App\Models\Jadwal;
use App\Models\User;
use App\Services\AutoScheduleService;
use App\Services\ScheduleConflictService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutoScheduleTransactionTest extends TestCase
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

    public function test_assignment_is_rolled_back_when_audit_log_insert_fails(): void
    {
        $workTypeId = DB::table('jenis_pekerjaan')->insertGetId(['nama_pekerjaan' => 'Panen']);
        $scheduleId = DB::table('jadwal')->insertGetId([
            'jenis_pekerjaan_id' => $workTypeId,
            'nama_kegiatan' => 'Panen uji',
            'tanggal' => '2026-11-10',
            'tanggal_selesai' => '2026-11-10',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '12:00:00',
            'jumlah_orang_dibutuhkan' => 1,
            'metode_penjadwalan' => 'otomatis',
            'status' => 'scheduled',
        ]);
        $memberId = DB::table('users')->insertGetId([
            'nomor_anggota' => 'TEST-001',
            'nama_lengkap' => 'Anggota Uji',
            'role' => 'anggota',
            'status_aktif' => true,
        ]);
        $schedule = Jadwal::query()->findOrFail($scheduleId);
        Schema::drop('rekomendasi_penugasan_log');

        try {
            app(AutoScheduleService::class)->generateForSchedule(
                $schedule,
                app(ScheduleConflictService::class),
                [$memberId],
            );
            self::fail('Kegagalan insert log seharusnya menggagalkan transaksi.');
        } catch (QueryException) {
            self::assertSame(0, DB::table('penugasan')->count());
        }
    }
}
