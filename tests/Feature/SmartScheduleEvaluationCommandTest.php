<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\KompetensiAnggota;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartScheduleEvaluationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_reports_population_metrics_and_exports_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status_aktif' => true]);
        $member = User::factory()->create(['role' => 'anggota', 'status_aktif' => true]);
        User::factory()->create(['role' => 'anggota', 'status_aktif' => true]);
        $job = JenisPekerjaan::create([
            'nama_pekerjaan' => 'Panen',
            'deskripsi' => 'Panen',
            'satuan' => 'karung',
            'created_by' => $admin->id,
        ]);
        $schedule = Jadwal::create([
            'jenis_pekerjaan_id' => $job->id,
            'nama_kegiatan' => 'Panen November',
            'tanggal' => '2026-11-02',
            'tanggal_selesai' => '2026-11-02',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '12:00',
            'jumlah_orang_dibutuhkan' => 1,
            'metode_penjadwalan' => 'manual',
            'status' => 'scheduled',
            'created_by' => $admin->id,
        ]);
        $assignment = Penugasan::create([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $member->id,
            'original_anggota_id' => $member->id,
            'status' => 'completed',
            'sumber_penugasan' => 'otomatis',
        ]);
        KompetensiAnggota::create([
            'anggota_id' => $member->id,
            'jenis_pekerjaan_id' => $job->id,
            'tingkat_kemampuan' => 'mahir',
        ]);
        $csvPath = tempnam(sys_get_temp_dir(), 'smart-evaluasi-');
        $this->assertNotFalse($csvPath);

        try {
            $this->artisan('smart:evaluasi', [
                '--dari' => '2026-11-02',
                '--sampai' => '2026-11-03',
                '--csv' => $csvPath,
            ])
                ->expectsOutputToContain('SD jam populasi: 2,0000')
                ->expectsOutputToContain('Gini: 0,5000')
                ->expectsOutputToContain('Rasio maksimum/rata-rata: 2,0000')
                ->expectsOutputToContain('Rata-rata level kompetensi per penugasan: 3,0000')
                ->assertExitCode(0);

            $this->assertStringContainsString('penugasan_otomatis,1', file_get_contents($csvPath));
            $this->assertDatabaseHas('penugasan', ['id' => $assignment->id]);
        } finally {
            unlink($csvPath);
        }
    }

    public function test_evaluation_rejects_invalid_date_ranges(): void
    {
        $this->artisan('smart:evaluasi', [
            '--dari' => '2026-11-03',
            '--sampai' => '2026-11-02',
        ])
            ->expectsOutputToContain('Tanggal sampai tidak boleh lebih awal')
            ->assertExitCode(1);
    }
}
