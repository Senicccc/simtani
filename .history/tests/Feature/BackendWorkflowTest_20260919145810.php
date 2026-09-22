<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\Penugasan;
use App\Models\PermintaanPerubahan;
use App\Models\Presensi;
use App\Models\Upah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_schema_and_relationships_are_available(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $job = $this->job($admin);
        $schedule = $this->schedule($admin, $job);
        $assignment = $this->assignment($schedule, $member);

        Presensi::create([
            'penugasan_id' => $assignment->id,
            'waktu_check_in' => now(),
            'status' => 'hadir',
        ]);
        Upah::create([
            'penugasan_id' => $assignment->id,
            'mode_perhitungan' => 'manual',
            'tarif_per_satuan' => 100000,
            'jumlah_satuan' => 1,
            'jumlah_upah' => 100000,
            'status_pembayaran' => 'belum_dibayar',
        ]);

        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id]);
        $this->assertSame($job->id, $schedule->jenisPekerjaan->id);
        $this->assertSame($member->id, $assignment->anggota->id);
        $this->assertTrue($assignment->presensi()->exists());
        $this->assertTrue($assignment->upah()->exists());
    }

    public function test_admin_can_create_member_and_job_type(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $memberResponse = $this->post(route('admin.anggota.store'), [
            'nomor_anggota' => 'ANG-100',
            'nama_lengkap' => 'Anggota Baru',
            'email' => 'baru@example.test',
            'password' => 'password',
            'nomor_wa' => '081234567890',
            'alamat' => 'Desa Baru',
            'status_aktif' => 1,
        ]);
        $memberResponse->assertRedirect(route('admin.anggota.index'));
        $this->assertDatabaseHas('users', ['nomor_anggota' => 'ANG-100', 'role' => 'anggota']);

        $jobResponse = $this->post(route('admin.jenis-pekerjaan.store'), [
            'nama_pekerjaan' => 'Penyiraman',
            'deskripsi' => 'Penyiraman tanaman',
            'satuan' => 'petak',
            'tarif_default' => 125000,
            'status_aktif' => 1,
        ]);
        $jobResponse->assertRedirect(route('admin.jenis-pekerjaan.index'));
        $this->assertDatabaseHas('jenis_pekerjaan', ['nama_pekerjaan' => 'Penyiraman']);
    }

    public function test_roles_are_restricted_to_their_own_backend(): void
    {
        $this->actingAs($this->member())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('anggota.dashboard'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($this->member())->get(route('anggota.dashboard'))->assertOk();
    }

    public function test_admin_can_create_and_update_schedule(): void
    {
        $admin = $this->admin();
        $job = $this->job($admin);
        $this->actingAs($admin);

        $response = $this->post(route('admin.jadwal.store'), [
            'jenis_pekerjaan_id' => $job->id,
            'nama_kegiatan' => 'Jadwal Baru',
            'deskripsi' => 'Deskripsi jadwal',
            'lokasi' => 'Lahan A',
            'tanggal' => now()->addDay()->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '12:00',
            'jumlah_orang_dibutuhkan' => 2,
            'jumlah_satuan' => 1,
        ]);
        $schedule = Jadwal::where('nama_kegiatan', 'Jadwal Baru')->firstOrFail();
        $response->assertRedirect(route('admin.jadwal.show', $schedule));

        $this->put(route('admin.jadwal.update', $schedule), [
            'jenis_pekerjaan_id' => $job->id,
            'nama_kegiatan' => 'Jadwal Diperbarui',
            'tanggal' => now()->addDays(2)->toDateString(),
            'jumlah_orang_dibutuhkan' => 1,
            'jumlah_satuan' => 2,
            'metode_penjadwalan' => 'manual',
            'status' => 'scheduled',
        ])->assertRedirect(route('admin.jadwal.show', $schedule));

        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id, 'nama_kegiatan' => 'Jadwal Diperbarui']);
    }

    public function test_member_can_start_and_complete_assignment(): void
    {
        $member = $this->member();
        $assignment = $this->assignment($this->schedule($this->admin(), $this->job($this->admin())), $member);
        $this->actingAs($member);

        $this->post(route('anggota.tugas.mulai', $assignment))->assertRedirect(route('anggota.tugas.show', $assignment));
        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'status' => 'in_progress']);

        $this->post(route('anggota.tugas.complete', $assignment), [
            'catatan_penyelesaian' => 'Selesai dikerjakan',
        ])->assertRedirect(route('anggota.tugas.index'));
        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'status' => 'waiting_verification']);
    }

    public function test_member_can_check_in_and_check_out(): void
    {
        $member = $this->member();
        $assignment = $this->assignment($this->schedule($this->admin(), $this->job($this->admin())), $member);
        $this->actingAs($member);

        $this->post(route('anggota.presensi.check-in', $assignment))->assertRedirect(route('anggota.presensi.index'));
        $this->assertDatabaseHas('presensi', ['penugasan_id' => $assignment->id, 'status' => 'hadir']);

        $this->post(route('anggota.presensi.check-out', $assignment))->assertRedirect(route('anggota.presensi.index'));
        $this->assertNotNull(Presensi::where('penugasan_id', $assignment->id)->firstOrFail()->waktu_check_out);
    }

    public function test_admin_can_verify_assignment_and_mark_wage_paid(): void
    {
        $admin = $this->admin();
        $assignment = $this->assignment($this->schedule($admin, $this->job($admin)), $this->member(), 'waiting_verification');
        $wage = Upah::create([
            'penugasan_id' => $assignment->id,
            'mode_perhitungan' => 'manual',
            'tarif_per_satuan' => 150000,
            'jumlah_satuan' => 1,
            'jumlah_upah' => 150000,
            'status_pembayaran' => 'belum_dibayar',
        ]);
        $this->actingAs($admin);

        $this->post(route('admin.penugasan.verify', $assignment))->assertRedirect(route('admin.penugasan.show', $assignment));
        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'status' => 'completed', 'verified_by' => $admin->id]);

        $this->post(route('admin.upah.mark-paid', $wage))->assertRedirect(route('admin.upah.index'));
        $this->assertDatabaseHas('upah', ['id' => $wage->id, 'status_pembayaran' => 'sudah_dibayar', 'dibayar_oleh' => $admin->id]);
    }

    public function test_admin_can_view_report_and_process_change_request(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $targetMember = $this->member('ANG-002', 'target@example.test');
        $job = $this->job($admin);
        $first = $this->assignment($this->schedule($admin, $job), $member);
        $second = $this->assignment($this->schedule($admin, $job), $targetMember);
        $request = PermintaanPerubahan::create([
            'penugasan_id' => $first->id,
            'diajukan_oleh' => $member->id,
            'jenis_permintaan' => 'tukar',
            'target_penugasan_id' => $second->id,
            'alasan' => 'Pertukaran jadwal',
            'status' => 'pending',
        ]);
        $this->actingAs($admin);

        $this->get(route('admin.laporan.index'))->assertOk();
        $this->post(route('admin.permintaan-perubahan.process', $request), ['action' => 'approve'])->assertRedirect(route('admin.permintaan-perubahan.index'));
        $this->assertDatabaseHas('permintaan_perubahan', ['id' => $request->id, 'status' => 'approved', 'diproses_oleh' => $admin->id]);
        $this->assertDatabaseHas('penugasan', ['id' => $first->id, 'anggota_id' => $targetMember->id]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'nomor_anggota' => 'ADM-' . fake()->unique()->numerify('###'),
            'role' => 'admin',
            'status_aktif' => true,
        ]);
    }

    private function member(?string $number = null, ?string $email = null): User
    {
        return User::factory()->create([
            'nomor_anggota' => $number ?? 'ANG-' . fake()->unique()->numerify('###'),
            'email' => $email ?? fake()->unique()->safeEmail(),
            'role' => 'anggota',
            'status_aktif' => true,
        ]);
    }

    private function job(User $admin): JenisPekerjaan
    {
        return JenisPekerjaan::create([
            'nama_pekerjaan' => 'Penanaman ' . fake()->unique()->numerify('###'),
            'deskripsi' => 'Pekerjaan pertanian',
            'satuan' => 'petak',
            'tarif_default' => 150000,
            'status_aktif' => true,
            'created_by' => $admin->id,
        ]);
    }

    private function schedule(User $admin, JenisPekerjaan $job): Jadwal
    {
        return Jadwal::create([
            'jenis_pekerjaan_id' => $job->id,
            'nama_kegiatan' => 'Kegiatan ' . fake()->unique()->numerify('###'),
            'deskripsi' => 'Kegiatan uji',
            'lokasi' => 'Lahan uji',
            'tanggal' => now()->addDay()->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '12:00',
            'jumlah_orang_dibutuhkan' => 1,
            'jumlah_satuan' => 1,
            'metode_penjadwalan' => 'manual',
            'status' => 'scheduled',
            'created_by' => $admin->id,
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
