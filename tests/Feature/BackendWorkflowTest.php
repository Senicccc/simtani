<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\KompetensiAnggota;
use App\Models\Penugasan;
use App\Models\PermintaanPerubahan;
use App\Models\Presensi;
use App\Models\Upah;
use App\Models\User;
use App\Services\JadwalStatusService;
use App\Services\ScheduleConflictService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackendWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_schema_and_relationships_are_available(): void
    {
        $this->assertFalse(Schema::hasColumn('jenis_pekerjaan', 'status_aktif'));
        $this->assertTrue(Schema::hasColumn('jadwal', 'total_upah'));
        $this->assertTrue(Schema::hasColumn('jadwal', 'perlu_verifikasi'));
        $this->assertTrue(Schema::hasColumn('jadwal', 'tanggal_selesai'));
        $this->assertTrue(Schema::hasColumn('presensi', 'tanggal'));
        $this->assertTrue(Schema::hasColumn('permintaan_perubahan', 'target_jadwal_id'));
        $this->assertTrue(Schema::hasColumn('penugasan', 'catatan_perubahan'));

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
        $this->get(route('admin.anggota.create'))->assertOk()->assertSee('Nomor anggota dibuat otomatis');

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

        $this->post(route('admin.anggota.store'), [
            'nama_lengkap' => 'Anggota Tanpa Nomor',
            'email' => 'tanpa-nomor@example.test',
            'password' => 'password',
            'nomor_wa' => '081234567891',
            'alamat' => 'Desa Baru',
            'status_aktif' => 1,
        ])->assertRedirect(route('admin.anggota.index'));
        $this->assertNotEmpty(User::where('email', 'tanpa-nomor@example.test')->firstOrFail()->nomor_anggota);

        $jobResponse = $this->post(route('admin.jenis-pekerjaan.store'), [
            'nama_pekerjaan' => 'Penyiraman',
            'deskripsi' => 'Penyiraman tanaman',
            'satuan' => 'petak',
        ]);
        $jobResponse->assertRedirect(route('admin.jenis-pekerjaan.index'));
        $this->assertDatabaseHas('jenis_pekerjaan', ['nama_pekerjaan' => 'Penyiraman']);
        $this->get(route('admin.jenis-pekerjaan.index'))->assertDontSee('Tarif');
    }

    public function test_validation_errors_are_displayed_in_indonesian(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jenis-pekerjaan.store'), [])
            ->assertSessionHasErrors([
                'nama_pekerjaan' => 'Nama pekerjaan wajib diisi.',
            ]);
    }

    public function test_admin_can_deactivate_a_member_without_deleting_history(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $schedule = $this->schedule($admin, $this->job($admin));
        $assignment = $this->assignment($schedule, $member);
        $originalPassword = $member->password;
        $this->actingAs($admin);

        $this->put(route('admin.anggota.update', $member), [
            'nama_lengkap' => $member->nama_lengkap,
            'email' => $member->email,
            'status_aktif' => '0',
        ])->assertRedirect(route('admin.anggota.index'));

        $this->assertDatabaseHas('users', ['id' => $member->id, 'status_aktif' => false]);
        $this->assertSame($member->nomor_anggota, $member->fresh()->nomor_anggota);
        $this->assertSame($originalPassword, $member->fresh()->password);
        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'anggota_id' => $member->id]);
        $this->get(route('admin.anggota.index', ['status' => '0']))->assertOk()->assertSee($member->nama_lengkap);
    }

    public function test_roles_are_restricted_to_their_own_backend(): void
    {
        $this->actingAs($this->member())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('anggota.dashboard'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($this->member())->get(route('anggota.dashboard'))->assertOk();
    }

    public function test_admin_dashboard_counters_link_to_matching_filtered_pages(): void
    {
        $admin = $this->admin();
        $job = $this->job($admin);

        $scheduled = $this->schedule($admin, $job);
        $scheduled->update([
            'tanggal' => today()->addDays(3)->toDateString(),
            'tanggal_selesai' => today()->addDays(3)->toDateString(),
        ]);

        $ongoing = $this->schedule($admin, $job);
        $this->assignment($ongoing, $this->member());

        $completed = $this->schedule($admin, $job);
        $completed->update([
            'tanggal' => today()->subDay()->toDateString(),
            'tanggal_selesai' => today()->subDay()->toDateString(),
        ]);
        $unpaidMember = $this->member();
        $completedAssignment = $this->assignment($completed, $unpaidMember, 'completed');
        Upah::create([
            'penugasan_id' => $completedAssignment->id,
            'mode_perhitungan' => 'manual',
            'jumlah_upah' => 150000,
            'status_pembayaran' => 'belum_dibayar',
        ]);

        $this->actingAs($admin);
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Daftar upah belum dibayar')
            ->assertSee($unpaidMember->nama_lengkap)
            ->assertSee($completed->nama_kegiatan)
            ->assertSee('Terjadwal')
            ->assertSee('Sedang berlangsung')
            ->assertSee('Jadwal selesai')
            ->assertSee(route('admin.jadwal.index'), false)
            ->assertSee(route('admin.jadwal.index', ['status' => 'scheduled']), false)
            ->assertSee(route('admin.jadwal.index', ['status' => 'ongoing']), false)
            ->assertSee(route('admin.jadwal.index', ['status' => 'completed']), false)
            ->assertSee(route('admin.jadwal.index', ['needs_replacement' => 1]), false)
            ->assertSee(route('admin.penugasan.index', ['status' => 'active']), false)
            ->assertSee(route('admin.penugasan.index', ['status' => 'waiting_verification']), false)
            ->assertSee(route('admin.permintaan-perubahan.index', ['status' => 'pending']), false)
            ->assertSee(route('admin.upah.index', ['status_pembayaran' => 'belum_dibayar']), false);

        $this->get(route('admin.jadwal.index', ['status' => 'completed']))
            ->assertOk()
            ->assertViewHas('jadwal', fn ($schedules) => $schedules->contains('id', $completed->id)
                && ! $schedules->contains('id', $scheduled->id));
        $this->get(route('admin.penugasan.index', ['status' => 'active']))
            ->assertOk()
            ->assertViewHas('jadwal', fn ($schedules) => $schedules->contains('id', $ongoing->id)
                && ! $schedules->contains('id', $completed->id));
        $this->get(route('admin.upah.index', [
            'status_pembayaran' => 'belum_dibayar',
            'jadwal_id' => $completed->id,
        ]))
            ->assertOk()
            ->assertSee($completed->nama_kegiatan)
            ->assertSee($unpaidMember->nama_lengkap)
            ->assertDontSee($scheduled->nama_kegiatan);
    }

    public function test_member_can_view_admin_contact_information(): void
    {
        $admin = $this->admin();

        $this->actingAs($this->member())
            ->get(route('anggota.hubungi-admin'))
            ->assertOk()
            ->assertSee($admin->nama_lengkap)
            ->assertSee($admin->email);
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
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '12:00:00',
            'jumlah_orang_dibutuhkan' => 2,
            'jumlah_satuan' => 1,
            'total_upah' => 85000,
            'perlu_verifikasi' => 1,
        ]);
        $schedule = Jadwal::where('nama_kegiatan', 'Jadwal Baru')->firstOrFail();
        $response->assertRedirect(route('admin.jadwal.show', $schedule));
        $this->get(route('admin.jadwal.edit', $schedule))->assertOk()->assertSee('value="08:00"', false);

        $this->put(route('admin.jadwal.update', $schedule), [
            'jenis_pekerjaan_id' => $job->id,
            'nama_kegiatan' => 'Jadwal Diperbarui',
            'tanggal' => now()->addDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(2)->toDateString(),
            'waktu_mulai' => '09:30:00',
            'waktu_selesai' => '13:30:00',
            'jumlah_orang_dibutuhkan' => 1,
            'jumlah_satuan' => 2,
            'total_upah' => 85000,
            'perlu_verifikasi' => 1,
            'metode_penjadwalan' => 'manual',
            'status' => 'scheduled',
        ])->assertRedirect(route('admin.jadwal.show', $schedule));

        $this->assertDatabaseHas('jadwal', [
            'id' => $schedule->id,
            'nama_kegiatan' => 'Jadwal Diperbarui',
            'waktu_mulai' => '09:30',
            'waktu_selesai' => '13:30',
            'total_upah' => 85000,
        ]);
    }

    public function test_admin_can_delete_unused_categories_and_schedules_but_not_referenced_records(): void
    {
        $admin = $this->admin();
        $unusedJob = $this->job($admin);
        $referencedJob = $this->job($admin);
        $schedule = $this->schedule($admin, $referencedJob);
        $competencyJob = $this->job($admin);
        KompetensiAnggota::create([
            'anggota_id' => $this->member()->id,
            'jenis_pekerjaan_id' => $competencyJob->id,
            'tingkat_kemampuan' => 'pemula',
        ]);
        $this->actingAs($admin);

        $this->delete(route('admin.jenis-pekerjaan.destroy', $unusedJob))
            ->assertRedirect(route('admin.jenis-pekerjaan.index'));
        $this->assertDatabaseMissing('jenis_pekerjaan', ['id' => $unusedJob->id]);

        $this->delete(route('admin.jenis-pekerjaan.destroy', $referencedJob))
            ->assertSessionHasErrors('hapus');
        $this->assertDatabaseHas('jenis_pekerjaan', ['id' => $referencedJob->id]);
        $this->delete(route('admin.jenis-pekerjaan.destroy', $competencyJob))
            ->assertSessionHasErrors('hapus');
        $this->assertDatabaseHas('jenis_pekerjaan', ['id' => $competencyJob->id]);

        $this->delete(route('admin.jadwal.destroy', $schedule))
            ->assertRedirect(route('admin.jadwal.index'));
        $this->assertDatabaseMissing('jadwal', ['id' => $schedule->id]);
    }

    public function test_schedule_with_assignment_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $schedule = $this->schedule($admin, $this->job($admin));
        $this->assignment($schedule, $this->member());
        $this->actingAs($admin);

        $this->delete(route('admin.jadwal.destroy', $schedule))
            ->assertRedirect(route('admin.jadwal.show', $schedule))
            ->assertSessionHasErrors('hapus');
        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id]);
    }

    public function test_admin_can_preview_and_generate_automatic_assignments_without_duplicates(): void
    {
        $admin = $this->admin();
        $job = $this->job($admin);
        $schedule = $this->schedule($admin, $job);
        $schedule->update(['jumlah_orang_dibutuhkan' => 2]);
        $members = [$this->member(), $this->member()];
        foreach ($members as $member) {
            KompetensiAnggota::create([
                'anggota_id' => $member->id,
                'jenis_pekerjaan_id' => $job->id,
                'tingkat_kemampuan' => 'menengah',
            ]);
        }
        $this->actingAs($admin);

        $this->getJson(route('admin.jadwal.auto-preview', $schedule))
            ->assertOk()
            ->assertJsonPath('required_slots', 2)
            ->assertJsonPath('assigned_count', 0)
            ->assertJsonPath('vacancies', 2)
            ->assertJsonPath('can_generate', true)
            ->assertJsonCount(2, 'candidates');

        $this->get(route('admin.jadwal.show', $schedule))
            ->assertOk()
            ->assertSee('Konfirmasi penugasan');
        $this->get(route('admin.jadwal.penugasan', $schedule))
            ->assertOk()
            ->assertSee('Cari anggota untuk mengisi 2 posisi yang tersisa');

        $this->post(route('admin.jadwal.generate', $schedule))
            ->assertRedirect(route('admin.jadwal.penugasan', $schedule));
        $this->assertDatabaseCount('penugasan', 2);

        $this->post(route('admin.jadwal.generate', $schedule))
            ->assertRedirect(route('admin.jadwal.penugasan', $schedule));
        $this->assertDatabaseCount('penugasan', 2);
    }

    public function test_admin_can_select_recommended_members_without_recorded_skills(): void
    {
        $admin = $this->admin();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update(['jumlah_orang_dibutuhkan' => 2]);
        $members = [$this->member(), $this->member(), $this->member(), $this->member()];
        $this->actingAs($admin);

        $this->getJson(route('admin.jadwal.auto-preview', $schedule))
            ->assertOk()
            ->assertJsonPath('candidate_count', 4)
            ->assertJsonPath('recommended_count', 2)
            ->assertJsonCount(4, 'candidates');

        $this->get(route('admin.jadwal.show', $schedule))
            ->assertOk()
            ->assertSee('Anggota tetap dapat dipilih meski kompetensinya belum dicatat.')
            ->assertSee('selected_member_ids[]', false);
        $this->get(route('admin.jadwal.penugasan', $schedule))
            ->assertOk()
            ->assertSee('anggota-search')
            ->assertSee('tugas aktif');

        $chosenMembers = [$members[2], $members[3]];
        $this->post(route('admin.jadwal.generate', $schedule), [
            'selected_member_ids_present' => 1,
            'selected_member_ids' => array_map(fn (User $member): int => $member->id, $chosenMembers),
        ])->assertRedirect(route('admin.jadwal.penugasan', $schedule));

        $this->assertDatabaseCount('penugasan', 2);
        foreach ($chosenMembers as $member) {
            $this->assertDatabaseHas('penugasan', [
                'jadwal_id' => $schedule->id,
                'anggota_id' => $member->id,
                'sumber_penugasan' => 'otomatis',
            ]);
        }
    }

    public function test_manual_assignment_only_rejects_overlapping_schedule_times(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $job = $this->job($admin);
        $firstSchedule = $this->schedule($admin, $job);
        $this->assignment($firstSchedule, $member);
        $nonOverlappingSchedule = $this->schedule($admin, $job);
        $nonOverlappingSchedule->update(['waktu_mulai' => '13:00', 'waktu_selesai' => '17:00']);
        $overlappingSchedule = $this->schedule($admin, $job);
        $overlappingSchedule->update(['waktu_mulai' => '11:00', 'waktu_selesai' => '14:00']);
        $this->assertTrue(app(ScheduleConflictService::class)->hasConflict($member->id, $overlappingSchedule));
        $this->actingAs($admin);

        $this->post(route('admin.penugasan.store'), [
            'jadwal_id' => $nonOverlappingSchedule->id,
            'anggota_id' => $member->id,
        ])->assertRedirect(route('admin.penugasan.show', Penugasan::latest('id')->first()));
        $this->assertDatabaseHas('penugasan', [
            'jadwal_id' => $nonOverlappingSchedule->id,
            'anggota_id' => $member->id,
            'status' => 'assigned',
        ]);

        $this->from(route('admin.penugasan.create'))->post(route('admin.penugasan.store'), [
            'jadwal_id' => $overlappingSchedule->id,
            'anggota_id' => $member->id,
        ])->assertRedirect(route('admin.penugasan.create'))
            ->assertSessionHasErrors('anggota_id');
        $this->assertDatabaseMissing('penugasan', [
            'jadwal_id' => $overlappingSchedule->id,
            'anggota_id' => $member->id,
        ]);
    }

    public function test_report_csv_exports_all_report_sections_and_rejects_invalid_date_ranges(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $schedule = $this->schedule($admin, $this->job($admin));
        $assignment = $this->assignment($schedule, $member);
        Presensi::create([
            'penugasan_id' => $assignment->id,
            'waktu_check_in' => now(),
            'status' => 'hadir',
        ]);
        Upah::create([
            'penugasan_id' => $assignment->id,
            'mode_perhitungan' => 'manual',
            'jumlah_upah' => 100000,
            'status_pembayaran' => 'belum_dibayar',
        ]);
        $this->actingAs($admin);

        $response = $this->get(route('admin.laporan.export.csv'));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('PENUGASAN', $csv);
        $this->assertStringContainsString('PRESENSI', $csv);
        $this->assertStringContainsString('UPAH', $csv);
        $this->assertStringContainsString($member->nama_lengkap, $csv);
        $this->get(route('admin.laporan.export'))
            ->assertOk()
            ->assertSee('Terjadwal')
            ->assertSee('Ditugaskan')
            ->assertSee('Hadir')
            ->assertSee('Belum dibayar');

        $this->get(route('admin.laporan.index', [
            'tanggal_mulai' => '2026-10-10',
            'tanggal_selesai' => '2026-10-09',
        ]))->assertSessionHasErrors('tanggal_selesai');
    }

    public function test_member_can_start_and_complete_assignment(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update(['tanggal_selesai' => today()->addDay()]);
        $assignment = $this->assignment($schedule, $member);
        $this->actingAs($member);

        $this->post(route('anggota.tugas.mulai', $assignment))->assertRedirect(route('anggota.tugas.show', $assignment));
        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('presensi', ['penugasan_id' => $assignment->id, 'status' => 'hadir']);
        $this->get(route('anggota.tugas.complete.form', $assignment))->assertOk()->assertSee('type="date"', false);

        $this->post(route('anggota.tugas.complete', $assignment), [
            'tanggal_selesai' => today()->toDateString(),
            'catatan_penyelesaian' => 'Selesai dikerjakan',
        ])->assertRedirect(route('anggota.tugas.index'));
        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'status' => 'waiting_verification']);
        $this->assertSame(today()->toDateString(), $assignment->fresh()->completed_at->toDateString());
        $this->assertNotNull($assignment->fresh()->presensi->waktu_check_out);
        $this->assertDatabaseMissing('upah', ['penugasan_id' => $assignment->id]);
        $this->actingAs($admin)->get(route('admin.upah.index'))->assertOk()->assertSee('Laporan yang perlu diperiksa')->assertSee($member->nama_lengkap);
        $this->actingAs($member)->get(route('anggota.upah.index'))->assertOk()->assertSee('Menunggu pemeriksaan');
        $this->get(route('anggota.riwayat-tugas'))->assertOk()->assertSee('Belum ada tugas selesai.');

        $this->actingAs($admin)->post(route('admin.penugasan.verify', $assignment))
            ->assertRedirect(route('admin.penugasan.show', $assignment));
        $this->get(route('admin.penugasan.show', $assignment))
            ->assertOk()
            ->assertSee('Lihat upah kegiatan ini')
            ->assertSee(route('admin.upah.index', ['jadwal_id' => $schedule->id]), false);
        $this->assertSame('completed', $schedule->fresh()->status);
    }

    public function test_member_cannot_request_a_change_after_checking_in(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $assignment = $this->assignment($this->schedule($admin, $this->job($admin)), $member);
        $this->actingAs($member)->post(route('anggota.tugas.mulai', $assignment));

        $this->post(route('anggota.permintaan-perubahan.store'), [
            'penugasan_id' => $assignment->id,
            'jenis_permintaan' => 'sanggah',
            'alasan' => 'Tidak bisa melanjutkan.',
        ])->assertSessionHasErrors('penugasan_id');

        $this->assertDatabaseCount('permintaan_perubahan', 0);
    }

    public function test_member_cannot_select_or_submit_an_assignment_with_a_pending_change_request(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $pendingAssignment = $this->assignment($this->schedule($admin, $this->job($admin)), $member);
        $availableAssignment = $this->assignment($this->schedule($admin, $this->job($admin)), $member);
        PermintaanPerubahan::create([
            'penugasan_id' => $pendingAssignment->id,
            'diajukan_oleh' => $member->id,
            'jenis_permintaan' => 'sanggah',
            'alasan' => 'Tidak dapat mengerjakan tugas.',
            'status' => 'pending',
        ]);
        $this->actingAs($member)
            ->get(route('anggota.permintaan-perubahan.create'))
            ->assertOk()
            ->assertDontSee('value="'.$pendingAssignment->id.'"', false)
            ->assertSee('value="'.$availableAssignment->id.'"', false);

        $this->post(route('anggota.permintaan-perubahan.store'), [
            'penugasan_id' => $pendingAssignment->id,
            'jenis_permintaan' => 'sanggah',
            'alasan' => 'Mengajukan lagi sebelum keputusan.',
        ])->assertSessionHasErrors('penugasan_id');

        $this->assertDatabaseCount('permintaan_perubahan', 1);
    }

    public function test_admin_can_reject_a_false_completion_report_without_awarding_wages(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $schedule = $this->schedule($admin, $this->job($admin));
        $assignment = $this->assignment($schedule, $member, 'waiting_verification');
        $assignment->update(['completed_at' => now()]);
        Presensi::create([
            'penugasan_id' => $assignment->id,
            'tanggal' => today()->toDateString(),
            'status' => 'hadir',
            'waktu_check_in' => now(),
            'waktu_check_out' => now(),
        ]);
        $this->actingAs($admin);

        $this->post(route('admin.penugasan.verify', $assignment), [
            'action' => 'reject',
            'catatan_verifikasi' => 'Bukti laporan tidak sesuai.',
        ])->assertRedirect(route('admin.penugasan.show', $assignment));

        $this->assertDatabaseHas('penugasan', [
            'id' => $assignment->id,
            'status' => 'rejected',
            'verified_by' => $admin->id,
            'catatan_verifikasi' => 'Bukti laporan tidak sesuai.',
        ]);
        $this->assertDatabaseHas('presensi', [
            'penugasan_id' => $assignment->id,
            'status' => 'tidak_hadir',
            'catatan' => 'Bukti laporan tidak sesuai.',
        ]);
        $this->assertDatabaseMissing('upah', ['penugasan_id' => $assignment->id]);
    }

    public function test_rejected_report_closes_expired_schedule_and_admin_can_extend_deadline(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update([
            'tanggal' => today()->subDays(2)->toDateString(),
            'tanggal_selesai' => today()->subDay()->toDateString(),
        ]);
        $assignment = $this->assignment($schedule, $member, 'waiting_verification');
        $assignment->update(['completed_at' => today()->subDay()]);
        Presensi::create([
            'penugasan_id' => $assignment->id,
            'tanggal' => today()->subDay()->toDateString(),
            'status' => 'hadir',
            'waktu_check_in' => today()->subDay()->setTime(8, 0),
            'waktu_check_out' => today()->subDay()->setTime(12, 0),
        ]);
        $this->actingAs($admin)->post(route('admin.penugasan.verify', $assignment), [
            'action' => 'reject',
            'catatan_verifikasi' => 'Laporan perlu diperbaiki.',
        ])->assertRedirect(route('admin.penugasan.show', $assignment));

        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id, 'status' => 'completed']);
        $this->get(route('admin.jadwal.show', $schedule))
            ->assertOk()
            ->assertSee('Jadwal ditutup karena tenggat berakhir')
            ->assertSee('Perpanjang atau ubah tanggal kegiatan');

        $this->put(route('admin.jadwal.update', $schedule), [
            'jenis_pekerjaan_id' => $schedule->jenis_pekerjaan_id,
            'nama_kegiatan' => $schedule->nama_kegiatan,
            'deskripsi' => $schedule->deskripsi,
            'lokasi' => $schedule->lokasi,
            'tanggal' => today()->addDay()->toDateString(),
            'tanggal_selesai' => today()->addDays(3)->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '12:00',
            'jumlah_orang_dibutuhkan' => 1,
            'jumlah_satuan' => 1,
            'total_upah' => $schedule->total_upah,
            'perlu_verifikasi' => 1,
        ])->assertRedirect(route('admin.jadwal.show', $schedule));

        $this->assertDatabaseHas('jadwal', [
            'id' => $schedule->id,
            'status' => 'scheduled',
        ]);
        $this->assertSame(
            today()->addDays(3)->toDateString(),
            $schedule->refresh()->tanggal_selesai->toDateString(),
        );
        $this->assertDatabaseHas('penugasan', [
            'id' => $assignment->id,
            'status' => 'cancelled',
            'catatan_verifikasi' => 'Laporan perlu diperbaiki.',
        ]);
        $this->get(route('admin.jadwal.penugasan', $schedule))
            ->assertOk()
            ->assertSee('Tambah Anggota');
    }

    public function test_rejected_report_is_excluded_from_the_shared_wage(): void
    {
        $admin = $this->admin();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update(['jumlah_orang_dibutuhkan' => 2]);
        $rejectedAssignment = $this->assignment($schedule, $this->member(), 'waiting_verification');
        $approvedAssignment = $this->assignment($schedule, $this->member(), 'waiting_verification');

        foreach ([$rejectedAssignment, $approvedAssignment] as $assignment) {
            $assignment->update(['completed_at' => now()]);
            Presensi::create([
                'penugasan_id' => $assignment->id,
                'tanggal' => today()->toDateString(),
                'status' => 'hadir',
                'waktu_check_in' => now(),
                'waktu_check_out' => now(),
            ]);
        }
        $this->actingAs($admin);

        $this->post(route('admin.penugasan.verify', $rejectedAssignment), [
            'action' => 'reject',
            'catatan_verifikasi' => 'Bukti tidak valid.',
        ])->assertRedirect(route('admin.penugasan.show', $rejectedAssignment));
        $this->assertDatabaseCount('upah', 0);

        $this->post(route('admin.penugasan.verify', $approvedAssignment), [
            'action' => 'approve',
        ])->assertRedirect(route('admin.penugasan.show', $approvedAssignment));

        $this->assertDatabaseMissing('upah', ['penugasan_id' => $rejectedAssignment->id]);
        $this->assertDatabaseHas('upah', [
            'penugasan_id' => $approvedAssignment->id,
            'jumlah_upah' => 150000,
            'status_pembayaran' => 'belum_dibayar',
        ]);
    }

    public function test_leave_is_daily_and_admin_can_assign_a_replacement_for_that_day(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $replacement = $this->member();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update(['tanggal_selesai' => today()->addDays(4), 'jumlah_orang_dibutuhkan' => 1]);
        $assignment = $this->assignment($schedule, $member);
        $leaveDate = today()->addDay();

        $this->actingAs($member)->post(route('anggota.presensi.status', $assignment), [
            'tanggal' => $leaveDate->toDateString(),
            'status' => 'izin',
            'catatan' => 'Ada keperluan keluarga.',
        ])->assertRedirect(route('anggota.tugas.show', $assignment));

        $this->assertDatabaseHas('presensi', [
            'penugasan_id' => $assignment->id,
            'tanggal' => $leaveDate->toDateString().' 00:00:00',
            'status' => 'izin',
        ]);
        $this->assertSame('assigned', $assignment->fresh()->status);
        $this->assertSame('ongoing', $schedule->fresh()->status);

        $this->actingAs($admin)->get(route('admin.jadwal.penugasan', $schedule))
            ->assertOk()
            ->assertSee('0 / 1')
            ->assertSee('Tugaskan pengganti');

        $this->post(route('admin.penugasan.remove', $assignment))
            ->assertSessionHasErrors('replacement_anggota_id');

        $this->post(route('admin.jadwal.pengganti-harian', $schedule), [
            'tanggal' => $leaveDate->toDateString(),
            'anggota_id' => $replacement->id,
        ])->assertRedirect(route('admin.jadwal.penugasan', $schedule));

        $this->assertDatabaseHas('penugasan', [
            'jadwal_id' => $schedule->id,
            'anggota_id' => $replacement->id,
            'tanggal_mulai' => $leaveDate->toDateString().' 00:00:00',
            'tanggal_selesai' => $leaveDate->toDateString().' 00:00:00',
        ]);
        $this->assertDatabaseHas('jadwal', ['id' => $schedule->id, 'status' => 'ongoing']);
    }

    public function test_admin_can_accept_reduced_daily_coverage_when_one_member_is_present(): void
    {
        $admin = $this->admin();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update(['jumlah_orang_dibutuhkan' => 2]);
        $absentAssignment = $this->assignment($schedule, $this->member());
        $presentAssignment = $this->assignment($schedule, $this->member());
        Presensi::create([
            'penugasan_id' => $absentAssignment->id,
            'tanggal' => today()->toDateString(),
            'status' => 'tidak_hadir',
        ]);
        Presensi::create([
            'penugasan_id' => $presentAssignment->id,
            'tanggal' => today()->toDateString(),
            'status' => 'hadir',
            'waktu_check_in' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.jadwal.terima-kekurangan', $schedule), ['tanggal' => today()->toDateString()])
            ->assertRedirect(route('admin.jadwal.penugasan', $schedule));

        $this->assertDatabaseHas('presensi', [
            'penugasan_id' => $absentAssignment->id,
            'coverage_accepted' => true,
        ]);
        $this->assertSame(0, app(JadwalStatusService::class)->pendingReplacementSlots($schedule));
        $this->get(route('admin.jadwal.penugasan', $schedule))
            ->assertOk()
            ->assertSee('Dilanjutkan dengan 1 anggota');
    }

    public function test_admin_can_remove_a_member_if_at_least_one_other_member_remains(): void
    {
        $admin = $this->admin();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update(['jumlah_orang_dibutuhkan' => 2]);
        $assignmentToRemove = $this->assignment($schedule, $this->member());
        $this->assignment($schedule, $this->member());

        $this->actingAs($admin)
            ->post(route('admin.penugasan.remove', $assignmentToRemove))
            ->assertRedirect(route('admin.penugasan.show', $assignmentToRemove));

        $this->assertDatabaseHas('penugasan', [
            'id' => $assignmentToRemove->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_leave_without_a_full_replacement_cannot_complete_the_schedule(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update(['tanggal_selesai' => today()->addDay(), 'jumlah_orang_dibutuhkan' => 1]);
        $assignment = $this->assignment($schedule, $member);

        $this->actingAs($member);
        foreach ([$schedule->tanggal, $schedule->tanggal_selesai] as $date) {
            $this->post(route('anggota.presensi.status', $assignment), [
                'tanggal' => $date->toDateString(),
                'status' => 'izin',
                'catatan' => 'Tidak dapat bekerja pada tanggal ini.',
            ])->assertRedirect(route('anggota.tugas.show', $assignment));
        }

        $this->assertSame('ongoing', $schedule->fresh()->status);
        $this->actingAs($admin);
        $this->assertSame(2, $this->get(route('admin.dashboard'))->assertOk()->viewData('leaveActions'));
        $this->actingAs($member)->post(route('anggota.tugas.complete', $assignment), [
            'tanggal_selesai' => today()->toDateString(),
            'catatan_penyelesaian' => 'Selesai',
        ])->assertSessionHasErrors('status');
        $this->assertSame('ongoing', $schedule->fresh()->status);
    }

    public function test_change_request_requires_a_valid_action(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $assignment = $this->assignment($this->schedule($admin, $this->job($admin)), $member);

        $this->actingAs($member)->post(route('anggota.permintaan-perubahan.store'), [
            'penugasan_id' => $assignment->id,
            'jenis_permintaan' => 'sanggah',
            'alasan' => 'Saya tidak dapat hadir pada waktu tersebut.',
        ])->assertRedirect(route('anggota.permintaan-perubahan.index'));

        $request = PermintaanPerubahan::firstOrFail();
        $this->actingAs($admin)->get(route('admin.permintaan-perubahan.show', $request))->assertOk();
        $this->actingAs($admin)->from(route('admin.permintaan-perubahan.show', $request))
            ->post(route('admin.permintaan-perubahan.process', $request), ['action' => 'invalid'])
            ->assertRedirect(route('admin.permintaan-perubahan.show', $request))
            ->assertSessionHasErrors('action');
        $this->assertSame('pending', $request->fresh()->status);

        $this->post(route('admin.permintaan-perubahan.process', $request), ['action' => 'reject'])
            ->assertRedirect(route('admin.permintaan-perubahan.index'));
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('assigned', $assignment->fresh()->status);
    }

    public function test_member_can_request_transfer_to_an_available_schedule(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $job = $this->job($admin);
        $source = $this->assignment($this->schedule($admin, $job), $member);
        $destination = $this->schedule($admin, $job);
        KompetensiAnggota::create([
            'anggota_id' => $member->id,
            'jenis_pekerjaan_id' => $job->id,
            'tingkat_kemampuan' => 'menengah',
        ]);

        $this->actingAs($member)->get(route('anggota.permintaan-perubahan.create'))
            ->assertOk()
            ->assertSee($destination->nama_kegiatan);

        $this->actingAs($member)
            ->post(route('anggota.permintaan-perubahan.store'), [
                'penugasan_id' => $source->id,
                'jenis_permintaan' => 'tukar',
                'target_jadwal_id' => $destination->id,
                'alasan' => 'Mohon pindah ke jadwal ini.',
            ])
            ->assertRedirect(route('anggota.permintaan-perubahan.index'));

        $this->assertDatabaseHas('permintaan_perubahan', [
            'penugasan_id' => $source->id,
            'target_jadwal_id' => $destination->id,
            'jenis_permintaan' => 'tukar',
            'status' => 'pending',
        ]);
    }

    public function test_approving_a_sanggah_without_destination_cancels_assignment_and_keeps_a_note(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $assignment = $this->assignment($this->schedule($admin, $this->job($admin)), $member);
        $request = PermintaanPerubahan::create([
            'penugasan_id' => $assignment->id,
            'diajukan_oleh' => $member->id,
            'jenis_permintaan' => 'sanggah',
            'alasan' => 'Saya tidak dapat mengerjakan jadwal ini.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.permintaan-perubahan.process', $request), ['action' => 'approve'])
            ->assertRedirect(route('admin.permintaan-perubahan.index'));

        $this->assertDatabaseHas('penugasan', [
            'id' => $assignment->id,
            'status' => 'cancelled',
            'catatan_perubahan' => 'Dibatalkan melalui sanggahan permintaan #'.$request->id.' tanpa pengganti.',
        ]);
        $this->assertDatabaseHas('permintaan_perubahan', [
            'id' => $request->id,
            'status' => 'approved',
            'target_jadwal_id' => null,
        ]);
    }

    public function test_member_start_records_attendance_and_completion_records_check_out(): void
    {
        $member = $this->member();
        $assignment = $this->assignment($this->schedule($this->admin(), $this->job($this->admin())), $member);
        $this->actingAs($member);

        $this->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('Lihat detail tugas dan presensi')
            ->assertDontSee('Mulai dan catat hadir hari ini');
        $this->get(route('anggota.tugas.index'))
            ->assertOk()
            ->assertSee('Lihat detail tugas dan presensi')
            ->assertDontSee('Mulai dan catat hadir hari ini');
        $this->get(route('anggota.tugas.show', $assignment))
            ->assertOk()
            ->assertSee('Detail kegiatan')
            ->assertSee('Lokasi')
            ->assertSee('Mulai dan catat hadir hari ini')
            ->assertSee('Apakah Anda yakin ingin check-in dan mulai tugas ini sekarang?', false);
        $this->post(route('anggota.tugas.mulai', $assignment))->assertRedirect(route('anggota.tugas.show', $assignment));
        $this->assertNotNull($assignment->fresh()->presensi->waktu_check_in);
        $this->get(route('anggota.tugas.show', $assignment))
            ->assertOk()
            ->assertSee('Hadir')
            ->assertSee('Laporkan tugas &amp; check-out', false)
            ->assertSee('Check-out saja')
            ->assertSee('Apakah Anda yakin ingin check-out dari tugas ini sekarang?', false);

        $this->post(route('anggota.tugas.check-out', $assignment))
            ->assertRedirect(route('anggota.tugas.show', $assignment));
        $this->assertSame('in_progress', $assignment->fresh()->status);
        $this->assertNotNull($assignment->fresh()->presensi->waktu_check_out);
        $this->get(route('anggota.tugas.show', $assignment))
            ->assertOk()
            ->assertDontSee('Check-out saja');

        $this->post(route('anggota.tugas.complete', $assignment), [
            'tanggal_selesai' => today()->toDateString(),
        ])->assertRedirect(route('anggota.tugas.index'));
        $this->assertNotNull(Presensi::where('penugasan_id', $assignment->id)->firstOrFail()->waktu_check_out);
    }

    public function test_member_sees_check_in_for_a_schedule_on_the_local_date_before_utc_midnight(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update([
            'tanggal' => '2026-10-10',
            'tanggal_selesai' => '2026-10-10',
        ]);
        $this->assignment($schedule, $member);

        $this->travelTo(Carbon::parse('2026-10-09 22:00:00', 'UTC'));

        try {
            $this->assertSame('Asia/Jakarta', config('app.timezone'));
            $this->assertSame('2026-10-10', today()->toDateString());

            $this->actingAs($member)
                ->get(route('anggota.dashboard'))
                ->assertOk()
                ->assertSee('Presensi hari ini')
                ->assertSee('Lihat detail tugas dan presensi');

            $this->get(route('anggota.tugas.index'))
                ->assertOk()
                ->assertSee('Lihat detail tugas dan presensi');

            $this->get(route('anggota.tugas.show', $schedule->penugasan()->first()))
                ->assertOk()
                ->assertSee('Mulai dan catat hadir hari ini · Check-in');
        } finally {
            $this->travelBack();
        }
    }

    public function test_auto_completed_task_creates_wage_and_appears_in_member_history(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update(['total_upah' => 92500, 'perlu_verifikasi' => false]);
        $assignment = $this->assignment($schedule, $member);
        $this->actingAs($member);

        $this->get(route('anggota.tugas.complete.form', $assignment))
            ->assertNotFound();
        $this->post(route('anggota.tugas.mulai', $assignment));
        $this->get(route('anggota.tugas.complete.form', $assignment))
            ->assertOk()
            ->assertSee('Total upah kegiatan Rp 92.500 dibagi rata setelah semua anggota selesai.');

        $this->post(route('anggota.tugas.complete', $assignment), [
            'tanggal_selesai' => today()->toDateString(),
        ])->assertRedirect(route('anggota.tugas.index'))
            ->assertSessionHas('success', 'Tugas selesai. Bagian upah tersedia setelah seluruh anggota menyelesaikan tugas.');

        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'status' => 'completed']);
        $this->assertDatabaseHas('upah', [
            'penugasan_id' => $assignment->id,
            'jumlah_upah' => 92500,
            'status_pembayaran' => 'belum_dibayar',
        ]);
        $this->get(route('anggota.riwayat-tugas'))->assertOk()->assertSee('92.500')->assertSee('Selesai pada');
        $this->get(route('anggota.upah.index'))->assertOk()->assertSee('92.500');
    }

    public function test_schedule_wage_is_split_evenly_after_all_assignments_are_completed(): void
    {
        $admin = $this->admin();
        $schedule = $this->schedule($admin, $this->job($admin));
        $schedule->update(['jumlah_orang_dibutuhkan' => 2, 'total_upah' => 1000.01]);
        $firstAssignment = $this->assignment($schedule, $this->member(), 'waiting_verification');
        $secondAssignment = $this->assignment($schedule, $this->member(), 'waiting_verification');
        $this->actingAs($admin);

        $this->post(route('admin.penugasan.verify', $firstAssignment))
            ->assertRedirect(route('admin.penugasan.show', $firstAssignment));
        $this->assertDatabaseCount('upah', 0);

        $this->post(route('admin.penugasan.verify', $secondAssignment))
            ->assertRedirect(route('admin.penugasan.show', $secondAssignment));
        $this->assertDatabaseHas('upah', [
            'penugasan_id' => $firstAssignment->id,
            'jumlah_upah' => 500.01,
            'status_pembayaran' => 'belum_dibayar',
        ]);
        $this->assertDatabaseHas('upah', [
            'penugasan_id' => $secondAssignment->id,
            'jumlah_upah' => 500,
            'status_pembayaran' => 'belum_dibayar',
        ]);
    }

    public function test_admin_can_verify_assignment_and_mark_wage_paid(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $assignment = $this->assignment($this->schedule($admin, $this->job($admin)), $member, 'waiting_verification');
        $wage = Upah::create([
            'penugasan_id' => $assignment->id,
            'mode_perhitungan' => 'manual',
            'tarif_per_satuan' => 150000,
            'jumlah_satuan' => 1,
            'jumlah_upah' => 150000,
            'status_pembayaran' => 'belum_dibayar',
        ]);
        $this->actingAs($admin);

        $this->post(route('admin.penugasan.verify', $assignment))
            ->assertRedirect(route('admin.penugasan.show', $assignment));
        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'status' => 'completed', 'verified_by' => $admin->id]);

        $this->post(route('admin.upah.mark-paid', $wage))->assertRedirect(route('admin.upah.index'));
        $this->assertDatabaseHas('upah', ['id' => $wage->id, 'status_pembayaran' => 'sudah_dibayar', 'dibayar_oleh' => $admin->id]);
    }

    public function test_verifying_work_creates_the_agreed_wage_for_attendance_and_member(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $job = $this->job($admin);
        $schedule = $this->schedule($admin, $job);
        $schedule->update(['jumlah_satuan' => 2]);
        $assignment = $this->assignment($schedule, $member, 'waiting_verification');
        Presensi::create([
            'penugasan_id' => $assignment->id,
            'status' => 'izin',
            'catatan' => 'Izin dicatat; admin mengonfirmasi pekerjaan selesai.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.penugasan.verify', $assignment))
            ->assertRedirect(route('admin.penugasan.show', $assignment));

        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'status' => 'completed']);
        $this->assertDatabaseHas('upah', [
            'penugasan_id' => $assignment->id,
            'mode_perhitungan' => 'manual',
            'jumlah_upah' => 150000,
            'status_pembayaran' => 'belum_dibayar',
        ]);

        $this->get(route('admin.upah.index', ['jadwal_id' => $schedule->id]))
            ->assertOk()
            ->assertSee('Upah kegiatan: '.$schedule->nama_kegiatan)
            ->assertSee($member->nama_lengkap)
            ->assertSee('Rp 150.000');

        $this->actingAs($member)
            ->get(route('anggota.upah.index'))
            ->assertOk()
            ->assertSee('Kegiatan')
            ->assertSee('Rp 150.000');

        $this->actingAs($admin)->post(route('admin.penugasan.verify', $assignment));
        $this->assertDatabaseCount('upah', 1);
    }

    public function test_admin_can_remove_assignment_without_a_staffing_gap_and_replace_another(): void
    {
        $admin = $this->admin();
        $schedule = $this->schedule($admin, $this->job($admin));
        $member = $this->member();
        $replacement = $this->member('ANG-003', 'replacement@example.test');
        $assignment = $this->assignment($schedule, $member);
        $this->assignment($schedule, $this->member());
        $this->actingAs($admin);

        $this->post(route('admin.penugasan.remove', $assignment))
            ->assertRedirect(route('admin.penugasan.show', $assignment));
        $this->assertDatabaseHas('penugasan', ['id' => $assignment->id, 'status' => 'cancelled', 'anggota_id' => $member->id]);

        $replacementSchedule = $this->schedule($admin, $this->job($admin));
        $replacementSchedule->update([
            'tanggal' => today()->addDay(),
            'tanggal_selesai' => today()->addDay(),
        ]);
        $secondAssignment = $this->assignment($replacementSchedule, $member);
        $this->post(route('admin.penugasan.remove', $secondAssignment), [
            'replacement_anggota_id' => $replacement->id,
        ])->assertRedirect(route('admin.penugasan.show', $secondAssignment));

        $this->assertDatabaseHas('penugasan', ['id' => $secondAssignment->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('penugasan', [
            'jadwal_id' => $replacementSchedule->id,
            'anggota_id' => $replacement->id,
            'status' => 'assigned',
            'sumber_penugasan' => 'hasil_perubahan',
        ]);
    }

    public function test_admin_can_view_report_and_process_change_request(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $job = $this->job($admin);
        $sourceSchedule = $this->schedule($admin, $job);
        $targetSchedule = $this->schedule($admin, $job);
        $first = $this->assignment($sourceSchedule, $member);
        KompetensiAnggota::create([
            'anggota_id' => $member->id,
            'jenis_pekerjaan_id' => $job->id,
            'tingkat_kemampuan' => 'menengah',
        ]);
        $request = PermintaanPerubahan::create([
            'penugasan_id' => $first->id,
            'diajukan_oleh' => $member->id,
            'jenis_permintaan' => 'tukar',
            'target_jadwal_id' => $targetSchedule->id,
            'alasan' => 'Pertukaran jadwal',
            'status' => 'pending',
        ]);
        $this->actingAs($admin);

        $this->get(route('admin.laporan.index'))->assertOk();
        $this->post(route('admin.permintaan-perubahan.process', $request), ['action' => 'approve'])->assertRedirect(route('admin.permintaan-perubahan.index'));
        $this->assertDatabaseHas('permintaan_perubahan', ['id' => $request->id, 'status' => 'approved', 'diproses_oleh' => $admin->id]);
        $this->assertDatabaseHas('penugasan', [
            'id' => $first->id,
            'anggota_id' => $member->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('penugasan', [
            'jadwal_id' => $targetSchedule->id,
            'anggota_id' => $member->id,
            'status' => 'assigned',
            'sumber_penugasan' => 'hasil_perubahan',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'nomor_anggota' => 'ADM-'.fake()->unique()->numerify('###'),
            'role' => 'admin',
            'status_aktif' => true,
        ]);
    }

    private function member(?string $number = null, ?string $email = null): User
    {
        return User::factory()->create([
            'nomor_anggota' => $number ?? 'ANG-'.fake()->unique()->numerify('###'),
            'email' => $email ?? fake()->unique()->safeEmail(),
            'role' => 'anggota',
            'status_aktif' => true,
        ]);
    }

    private function job(User $admin): JenisPekerjaan
    {
        return JenisPekerjaan::create([
            'nama_pekerjaan' => 'Penanaman '.fake()->unique()->numerify('###'),
            'deskripsi' => 'Pekerjaan pertanian',
            'satuan' => 'petak',
            'created_by' => $admin->id,
        ]);
    }

    private function schedule(User $admin, JenisPekerjaan $job): Jadwal
    {
        return Jadwal::create([
            'jenis_pekerjaan_id' => $job->id,
            'nama_kegiatan' => 'Kegiatan '.fake()->unique()->numerify('###'),
            'deskripsi' => 'Kegiatan uji',
            'lokasi' => 'Lahan uji',
            'tanggal' => today()->toDateString(),
            'tanggal_selesai' => today()->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '12:00',
            'jumlah_orang_dibutuhkan' => 1,
            'jumlah_satuan' => 1,
            'total_upah' => 150000,
            'perlu_verifikasi' => true,
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
