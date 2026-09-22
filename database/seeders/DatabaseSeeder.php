<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\KompetensiAnggota;
use App\Models\Penugasan;
use App\Models\PermintaanPerubahan;
use App\Models\Presensi;
use App\Models\Upah;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'nomor_anggota' => 'ADM-001',
            'nama_lengkap' => 'Admin SIMTANI',
            'email' => 'admin@simtani.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status_aktif' => true,
            'alamat' => 'Kantor Kelompok Tani',
            'nomor_wa' => '081234567890',
        ]);

        $memberData = [
            ['nomor_anggota' => 'ANG-001', 'nama_lengkap' => 'Budi Santoso', 'email' => 'anggota1@simtani.test'],
            ['nomor_anggota' => 'ANG-002', 'nama_lengkap' => 'Siti Aminah', 'email' => 'anggota2@simtani.test'],
            ['nomor_anggota' => 'ANG-003', 'nama_lengkap' => 'Rina Wijaya', 'email' => 'anggota3@simtani.test'],
            ['nomor_anggota' => 'ANG-004', 'nama_lengkap' => 'Dedi Prasetyo', 'email' => 'anggota4@simtani.test'],
            ['nomor_anggota' => 'ANG-005', 'nama_lengkap' => 'Maya Lestari', 'email' => 'anggota5@simtani.test'],
            ['nomor_anggota' => 'ANG-006', 'nama_lengkap' => 'Farhan Hidayat', 'email' => 'anggota6@simtani.test'],
            ['nomor_anggota' => 'ANG-007', 'nama_lengkap' => 'Laila Rahma', 'email' => 'anggota7@simtani.test'],
            ['nomor_anggota' => 'ANG-008', 'nama_lengkap' => 'Anton Nugroho', 'email' => 'anggota8@simtani.test'],
            ['nomor_anggota' => 'ANG-009', 'nama_lengkap' => 'Nila Putri', 'email' => 'anggota9@simtani.test'],
            ['nomor_anggota' => 'ANG-010', 'nama_lengkap' => 'Irfan Rahman', 'email' => 'anggota10@simtani.test'],
        ];

        $memberModels = [];
        foreach ($memberData as $member) {
            $memberModels[] = User::create([
                'nomor_anggota' => $member['nomor_anggota'],
                'nama_lengkap' => $member['nama_lengkap'],
                'email' => $member['email'],
                'password' => Hash::make('password'),
                'role' => 'anggota',
                'status_aktif' => true,
                'alamat' => 'Desa Sejahtera',
                'nomor_wa' => '0821'.random_int(1000000, 9999999),
            ]);
        }

        $jobTypes = [
            ['nama_pekerjaan' => 'Penanaman', 'satuan' => 'petak', 'tarif_default' => 150000, 'deskripsi' => 'Pekerjaan penanaman bibit dan persiapan lahan.'],
            ['nama_pekerjaan' => 'Pemupukan', 'satuan' => 'blok', 'tarif_default' => 200000, 'deskripsi' => 'Pemupukan tanaman sesuai jadwal.'],
            ['nama_pekerjaan' => 'Panen', 'satuan' => 'kg', 'tarif_default' => 300000, 'deskripsi' => 'Pekerjaan panen hasil pertanian.'],
            ['nama_pekerjaan' => 'Pembersihan Lahan', 'satuan' => 'hari', 'tarif_default' => 180000, 'deskripsi' => 'Membersihkan lahan dan gulma.'],
            ['nama_pekerjaan' => 'Pengangkutan', 'satuan' => 'truk', 'tarif_default' => 220000, 'deskripsi' => 'Mengangkut hasil panen ke lokasi pengolahan.'],
        ];

        $jobModels = [];
        foreach ($jobTypes as $job) {
            $jobModels[] = JenisPekerjaan::create([
                'nama_pekerjaan' => $job['nama_pekerjaan'],
                'deskripsi' => $job['deskripsi'],
                'satuan' => $job['satuan'],
                'tarif_default' => $job['tarif_default'],
                'status_aktif' => true,
                'created_by' => $admin->id,
            ]);
        }

        $competencyOrder = [
            'ANG-001' => ['Penanaman' => 'mahir', 'Pemupukan' => 'menengah'],
            'ANG-002' => ['Pemupukan' => 'mahir', 'Pembersihan Lahan' => 'menengah'],
            'ANG-003' => ['Panen' => 'mahir', 'Pengangkutan' => 'menengah'],
            'ANG-004' => ['Penanaman' => 'menengah', 'Panen' => 'pemula'],
            'ANG-005' => ['Pemupukan' => 'mahir', 'Penanaman' => 'menengah'],
            'ANG-006' => ['Pembersihan Lahan' => 'mahir', 'Pemupukan' => 'menengah'],
            'ANG-007' => ['Pengangkutan' => 'mahir', 'Panen' => 'menengah'],
            'ANG-008' => ['Penanaman' => 'pemula', 'Pembersihan Lahan' => 'menengah'],
            'ANG-009' => ['Pemupukan' => 'menengah', 'Pengangkutan' => 'mahir'],
            'ANG-010' => ['Panen' => 'mahir', 'Pemupukan' => 'pemula'],
        ];

        foreach ($memberModels as $member) {
            $data = $competencyOrder[$member->nomor_anggota] ?? [];
            foreach ($data as $jobName => $level) {
                $job = $jobModels[array_search($jobName, array_column($jobTypes, 'nama_pekerjaan'))];
                KompetensiAnggota::create([
                    'anggota_id' => $member->id,
                    'jenis_pekerjaan_id' => $job->id,
                    'tingkat_kemampuan' => $level,
                ]);
            }
        }

        $scheduleData = [
            ['jenis_pekerjaan_id' => 1, 'nama_kegiatan' => 'Persiapan Lahan Penanaman', 'tanggal' => now()->addDays(1)->toDateString(), 'waktu_mulai' => '08:00:00', 'waktu_selesai' => '12:00:00', 'jumlah_orang_dibutuhkan' => 3],
            ['jenis_pekerjaan_id' => 2, 'nama_kegiatan' => 'Pemupukan Blok Timur', 'tanggal' => now()->addDays(2)->toDateString(), 'waktu_mulai' => '09:00:00', 'waktu_selesai' => '13:00:00', 'jumlah_orang_dibutuhkan' => 2],
            ['jenis_pekerjaan_id' => 3, 'nama_kegiatan' => 'Panen Petak A', 'tanggal' => now()->addDays(3)->toDateString(), 'waktu_mulai' => '07:00:00', 'waktu_selesai' => '12:00:00', 'jumlah_orang_dibutuhkan' => 2],
        ];

        foreach ($scheduleData as $schedule) {
            $jadwal = Jadwal::create([
                'jenis_pekerjaan_id' => $schedule['jenis_pekerjaan_id'],
                'nama_kegiatan' => $schedule['nama_kegiatan'],
                'deskripsi' => 'Demo data kegiatan SIMTANI.',
                'lokasi' => 'Lahan Kelompok Tani',
                'tanggal' => $schedule['tanggal'],
                'waktu_mulai' => $schedule['waktu_mulai'],
                'waktu_selesai' => $schedule['waktu_selesai'],
                'jumlah_orang_dibutuhkan' => $schedule['jumlah_orang_dibutuhkan'],
                'jumlah_satuan' => 1,
                'metode_penjadwalan' => 'manual',
                'status' => 'scheduled',
                'created_by' => $admin->id,
            ]);

            foreach ($memberModels as $index => $member) {
                if ($index >= $schedule['jumlah_orang_dibutuhkan']) {
                    break;
                }

                $assignment = Penugasan::create([
                    'jadwal_id' => $jadwal->id,
                    'anggota_id' => $member->id,
                    'original_anggota_id' => $member->id,
                    'status' => $index === 0 ? 'in_progress' : 'assigned',
                    'sumber_penugasan' => 'manual',
                    'assigned_at' => now(),
                ]);

                if ($index === 0) {
                    Presensi::create([
                        'penugasan_id' => $assignment->id,
                        'waktu_check_in' => now()->subHour(),
                        'waktu_check_out' => now(),
                        'status' => 'hadir',
                    ]);
                }
            }
        }

        $completedAssignments = Penugasan::orderBy('id')->take(3)->get();
        foreach ($completedAssignments as $assignment) {
            $assignment->update([
                'status' => 'completed',
                'completed_at' => now()->subDays(rand(1, 10)),
                'verified_by' => $admin->id,
                'verified_at' => now()->subDays(rand(1, 9)),
            ]);

            Upah::create([
                'penugasan_id' => $assignment->id,
                'mode_perhitungan' => 'otomatis',
                'tarif_per_satuan' => 150000,
                'jumlah_satuan' => 1,
                'jumlah_upah' => 150000,
                'status_pembayaran' => 'belum_dibayar',
            ]);
        }

        $requester = $memberModels[0];
        $target = $memberModels[1];
        $sourceAssignment = Penugasan::where('anggota_id', $requester->id)->first();
        $targetAssignment = Penugasan::where('anggota_id', $target->id)->first();

        if ($sourceAssignment && $targetAssignment) {
            PermintaanPerubahan::create([
                'penugasan_id' => $sourceAssignment->id,
                'diajukan_oleh' => $requester->id,
                'jenis_permintaan' => 'tukar',
                'target_penugasan_id' => $targetAssignment->id,
                'alasan' => 'Saya ingin menukar tugas agar lebih sesuai dengan jadwal saya.',
                'status' => 'pending',
            ]);
        }
    }
}
