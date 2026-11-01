<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SmartDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SmartDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_seed_is_deterministic_and_starts_without_assignment_history(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(8, User::query()->where('role', 'anggota')->where('status_aktif', true)->count());
        $this->assertSame(0, User::query()->where('nama_lengkap', 'like', 'Anggota Demo %')->count());
        $this->assertDatabaseHas('users', ['nomor_anggota' => 'ANG-009', 'status_aktif' => false]);
        $this->assertDatabaseCount('kompetensi_anggota', 40);
        $this->assertSame(4, Jadwal::query()->count());
        $this->assertSame(0, Jadwal::query()->where('nama_kegiatan', 'like', 'Demo %')->count());
        $this->assertDatabaseCount('penugasan', 0);
        $this->assertDatabaseCount('presensi', 0);
        $this->assertSame(0, Jadwal::query()->where('tanggal', '<', '2026-11-02')->count());
        $this->assertSame(4, Jadwal::query()->distinct()->count('tanggal'));
        $this->assertEqualsCanonicalizing([
            'Panen cabai di petak utara',
            'Membersihkan saluran irigasi',
            'Pemupukan tanaman padi',
            'Menanam bibit cabai di petak selatan',
        ], Jadwal::query()->pluck('nama_kegiatan')->all());

        $scheduleCount = Jadwal::query()->count();
        $schedules = Jadwal::query()
            ->orderBy('id')
            ->get(['nama_kegiatan', 'tanggal', 'tanggal_selesai', 'waktu_mulai', 'waktu_selesai', 'jumlah_orang_dibutuhkan'])
            ->toArray();
        $competencies = DB::table('kompetensi_anggota')
            ->orderBy('anggota_id')
            ->orderBy('jenis_pekerjaan_id')
            ->get(['anggota_id', 'jenis_pekerjaan_id', 'tingkat_kemampuan'])
            ->toArray();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($scheduleCount, Jadwal::query()->count());
        $this->assertDatabaseCount('kompetensi_anggota', 40);
        $this->assertSame($schedules, Jadwal::query()
            ->orderBy('id')
            ->get(['nama_kegiatan', 'tanggal', 'tanggal_selesai', 'waktu_mulai', 'waktu_selesai', 'jumlah_orang_dibutuhkan'])
            ->toArray());
        $this->assertEquals($competencies, DB::table('kompetensi_anggota')
            ->orderBy('anggota_id')
            ->orderBy('jenis_pekerjaan_id')
            ->get(['anggota_id', 'jenis_pekerjaan_id', 'tingkat_kemampuan'])
            ->toArray());
    }

    public function test_demo_seed_refuses_to_delete_schedules_that_have_assignments(): void
    {
        $this->seed(DatabaseSeeder::class);
        $schedule = Jadwal::query()->firstOrFail();
        $member = User::query()->where('role', 'anggota')->where('status_aktif', true)->firstOrFail();
        Penugasan::create([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $member->id,
            'original_anggota_id' => $member->id,
            'status' => 'assigned',
            'sumber_penugasan' => 'manual',
        ]);

        try {
            $this->seed(SmartDemoSeeder::class);
            $this->fail('Seeding harus menolak penghapusan jadwal yang sudah ditugaskan.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Jadwal demo yang sudah memiliki penugasan tidak dapat dibuat ulang.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id]);
        $this->assertDatabaseHas('penugasan', ['jadwal_id' => $schedule->id]);
    }

    public function test_demo_seed_refuses_to_delete_legacy_members_that_have_assignment_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $schedule = Jadwal::query()->firstOrFail();
        $schedule->update(['nama_kegiatan' => 'Perawatan saluran air']);
        $member = User::create([
            'nomor_anggota' => 'ANG-010',
            'nama_lengkap' => 'Anggota Demo 10',
            'email' => 'anggota.10@simtani.local',
            'password' => 'anggota123',
            'role' => 'anggota',
            'status_aktif' => true,
            'alamat' => 'Desa Makmur, Kecamatan Sukamaju',
            'nomor_wa' => '081234568010',
        ]);
        Penugasan::create([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $member->id,
            'original_anggota_id' => $member->id,
            'status' => 'assigned',
            'sumber_penugasan' => 'manual',
        ]);

        try {
            $this->seed(SmartDemoSeeder::class);
            $this->fail('Seeding harus menolak penghapusan anggota demo yang memiliki riwayat tugas.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Anggota demo yang sudah memiliki riwayat tidak dapat dihapus.', $exception->getMessage());
        }

        $this->assertDatabaseHas('users', ['id' => $member->id]);
        $this->assertDatabaseHas('penugasan', ['anggota_id' => $member->id]);
    }
}
