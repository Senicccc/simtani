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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['nomor_anggota' => 'ADM-001'],
            [
                'nama_lengkap' => 'Admin SIMTANI',
                'email' => 'admin1@simtani.test',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status_aktif' => true,
                'alamat' => 'Sekretariat Kelompok Tani Makmur',
                'nomor_wa' => '081234567890',
            ],
        );

        $memberData = [
            ['nomor_anggota' => 'ANG-001', 'nama_lengkap' => 'Budi Santoso', 'email' => 'budi.santoso@simtani.local', 'nomor_wa' => '081234567801', 'status_aktif' => true],
            ['nomor_anggota' => 'ANG-002', 'nama_lengkap' => 'Siti Aminah', 'email' => 'siti.aminah@simtani.local', 'nomor_wa' => '081234567802', 'status_aktif' => true],
            ['nomor_anggota' => 'ANG-003', 'nama_lengkap' => 'Rina Wijaya', 'email' => 'rina.wijaya@simtani.local', 'nomor_wa' => '081234567803', 'status_aktif' => true],
            ['nomor_anggota' => 'ANG-004', 'nama_lengkap' => 'Dedi Prasetyo', 'email' => 'dedi.prasetyo@simtani.local', 'nomor_wa' => '081234567804', 'status_aktif' => true],
            ['nomor_anggota' => 'ANG-005', 'nama_lengkap' => 'Maya Lestari', 'email' => 'maya.lestari@simtani.local', 'nomor_wa' => '081234567805', 'status_aktif' => true],
            ['nomor_anggota' => 'ANG-006', 'nama_lengkap' => 'Farhan Hidayat', 'email' => 'farhan.hidayat@simtani.local', 'nomor_wa' => '081234567806', 'status_aktif' => true],
            ['nomor_anggota' => 'ANG-007', 'nama_lengkap' => 'Laila Rahma', 'email' => 'laila.rahma@simtani.local', 'nomor_wa' => '081234567807', 'status_aktif' => true],
            ['nomor_anggota' => 'ANG-008', 'nama_lengkap' => 'Anton Nugroho', 'email' => 'anton.nugroho@simtani.local', 'nomor_wa' => '081234567808', 'status_aktif' => true],
            ['nomor_anggota' => 'ANG-009', 'nama_lengkap' => 'Nila Putri', 'email' => 'nila.putri@simtani.local', 'nomor_wa' => '081234567809', 'status_aktif' => false],
        ];

        $members = [];
        foreach ($memberData as $member) {
            $members[$member['nomor_anggota']] = User::updateOrCreate(
                ['nomor_anggota' => $member['nomor_anggota']],
                [
                    'nama_lengkap' => $member['nama_lengkap'],
                    'email' => $member['email'],
                    'password' => Hash::make('anggota123'),
                    'role' => 'anggota',
                    'status_aktif' => $member['status_aktif'],
                    'alamat' => 'Desa Makmur, Kecamatan Sukamaju',
                    'nomor_wa' => $member['nomor_wa'],
                ],
            );
        }

        $jobData = [
            'Penanaman' => ['deskripsi' => 'Persiapan lahan dan penanaman bibit sesuai jarak tanam.', 'satuan' => 'petak'],
            'Pemupukan' => ['deskripsi' => 'Pemupukan tanaman sesuai dosis dan jadwal kelompok.', 'satuan' => 'blok'],
            'Panen' => ['deskripsi' => 'Pemanenan, pemilahan, dan pencatatan hasil panen.', 'satuan' => 'karung'],
            'Pengendalian Hama' => ['deskripsi' => 'Pemeriksaan tanaman dan pengendalian hama terpadu.', 'satuan' => 'petak'],
            'Pembersihan Lahan' => ['deskripsi' => 'Pembersihan gulma dan saluran air lahan pertanian.', 'satuan' => 'hari'],
        ];

        $jobs = [];
        foreach ($jobData as $name => $details) {
            $jobs[$name] = JenisPekerjaan::updateOrCreate(
                ['nama_pekerjaan' => $name],
                [...$details, 'created_by' => $admin->id],
            );
        }

        $competencies = [
            'ANG-001' => ['Panen' => 'mahir', 'Penanaman' => 'menengah', 'Pengendalian Hama' => 'menengah'],
            'ANG-002' => ['Pemupukan' => 'mahir', 'Penanaman' => 'menengah'],
            'ANG-003' => ['Panen' => 'menengah', 'Pengendalian Hama' => 'mahir', 'Penanaman' => 'menengah'],
            'ANG-004' => ['Pemupukan' => 'menengah', 'Pembersihan Lahan' => 'mahir'],
            'ANG-005' => ['Penanaman' => 'mahir', 'Pemupukan' => 'menengah'],
            'ANG-006' => ['Pengendalian Hama' => 'menengah', 'Pembersihan Lahan' => 'mahir'],
            'ANG-007' => ['Panen' => 'menengah', 'Pengendalian Hama' => 'menengah'],
            'ANG-008' => ['Penanaman' => 'pemula', 'Pemupukan' => 'menengah'],
            'ANG-009' => ['Panen' => 'mahir'],
        ];

        foreach ($competencies as $memberNumber => $jobLevels) {
            foreach ($jobLevels as $jobName => $level) {
                KompetensiAnggota::updateOrCreate(
                    ['anggota_id' => $members[$memberNumber]->id, 'jenis_pekerjaan_id' => $jobs[$jobName]->id],
                    ['tingkat_kemampuan' => $level],
                );
            }
        }

        $scheduleData = [
            'Panen Padi Petak Utara' => [
                'job' => 'Panen', 'date' => '2026-10-19', 'start' => '07:00', 'end' => '12:00',
                'needed' => 2, 'quantity' => 3, 'total_wage' => 270000, 'status' => 'completed',
            ],
            'Penanaman Bibit Petak Selatan' => [
                'job' => 'Penanaman', 'date' => '2026-10-20', 'start' => '07:00', 'end' => '11:30',
                'needed' => 2, 'quantity' => 2, 'total_wage' => 240000, 'status' => 'completed',
            ],
            'Pengendalian Hama Blok Barat' => [
                'job' => 'Pengendalian Hama', 'date' => '2026-10-20', 'start' => '13:00', 'end' => '16:00',
                'needed' => 1, 'quantity' => 2, 'total_wage' => 160000, 'status' => 'completed',
            ],
            'Pembersihan Saluran Irigasi' => [
                'job' => 'Pembersihan Lahan', 'date' => '2026-10-20', 'start' => '07:30', 'end' => '10:30',
                'needed' => 2, 'quantity' => 1, 'total_wage' => 180000, 'status' => 'cancelled',
            ],
            'Pemupukan Blok Timur' => [
                'job' => 'Pemupukan', 'date' => '2026-10-21', 'start' => '08:00', 'end' => '16:00',
                'needed' => 2, 'quantity' => 2, 'total_wage' => 280000, 'status' => 'ongoing',
            ],
            'Persiapan Tanam Petak Utara' => [
                'job' => 'Penanaman', 'date' => '2026-10-22', 'start' => '07:30', 'end' => '12:00',
                'needed' => 1, 'quantity' => 2, 'total_wage' => 120000, 'status' => 'scheduled',
            ],
            'Panen Sayuran Petak Selatan' => [
                'job' => 'Panen', 'date' => '2026-10-25', 'start' => '06:30', 'end' => '11:30',
                'needed' => 1, 'quantity' => 4, 'total_wage' => 180000, 'status' => 'scheduled',
            ],
        ];

        $schedules = [];
        foreach ($scheduleData as $name => $details) {
            $date = Carbon::parse($details['date']);
            $schedules[$name] = Jadwal::updateOrCreate(
                ['nama_kegiatan' => $name],
                [
                    'jenis_pekerjaan_id' => $jobs[$details['job']]->id,
                    'deskripsi' => $jobData[$details['job']]['deskripsi'],
                    'lokasi' => 'Lahan Kelompok Tani Makmur',
                    'tanggal' => $date->toDateString(),
                    'tanggal_selesai' => $date->toDateString(),
                    'waktu_mulai' => $details['start'],
                    'waktu_selesai' => $details['end'],
                    'jumlah_orang_dibutuhkan' => $details['needed'],
                    'jumlah_satuan' => $details['quantity'],
                    'total_upah' => $details['total_wage'],
                    'perlu_verifikasi' => true,
                    'metode_penjadwalan' => 'manual',
                    'status' => $details['status'],
                    'created_by' => $admin->id,
                ],
            );
        }

        $createAssignment = static function (
            Jadwal $schedule,
            User $member,
            string $status,
            ?User $originalMember = null,
            ?string $source = null,
        ) use ($admin): Penugasan {
            $startedAt = $schedule->tanggal->copy()->setTimeFromTimeString($schedule->waktu_mulai ?? '08:00');
            $completedAt = $schedule->tanggal->copy()->setTimeFromTimeString($schedule->waktu_selesai ?? '16:00');
            $assignedAt = $schedule->tanggal->copy()->subDays(2);
            if ($assignedAt->greaterThan(today())) {
                $assignedAt = today();
            }
            $isCompleted = in_array($status, ['waiting_verification', 'completed', 'rejected'], true);

            return Penugasan::updateOrCreate(
                ['jadwal_id' => $schedule->id, 'anggota_id' => $member->id],
                [
                    'original_anggota_id' => ($originalMember ?? $member)->id,
                    'status' => $status,
                    'sumber_penugasan' => $source ?? 'manual',
                    'assigned_at' => $assignedAt->setTime(9, 0),
                    'started_at' => in_array($status, ['in_progress', 'waiting_verification', 'completed', 'rejected'], true) ? $startedAt : null,
                    'completed_at' => $isCompleted ? $completedAt : null,
                    'verified_by' => $status === 'completed' ? $admin->id : null,
                    'verified_at' => $status === 'completed' ? $completedAt->copy()->addMinutes(15) : null,
                    'catatan_verifikasi' => $status === 'rejected' ? 'Bukti pekerjaan tidak sesuai kondisi di lapangan.' : null,
                ],
            );
        };

        $recordAttendance = static function (
            Penugasan $assignment,
            string $status,
            string $checkIn,
            ?string $checkOut,
            string $note,
        ): void {
            Presensi::updateOrCreate(
                ['penugasan_id' => $assignment->id],
                [
                    'tanggal' => $assignment->jadwal->tanggal->toDateString(),
                    'waktu_check_in' => Carbon::parse($assignment->jadwal->tanggal->toDateString().' '.$checkIn),
                    'waktu_check_out' => $checkOut
                        ? Carbon::parse($assignment->jadwal->tanggal->toDateString().' '.$checkOut)
                        : null,
                    'status' => $status,
                    'catatan' => $note,
                ],
            );
        };

        $recordWage = static function (
            Penugasan $assignment,
            float $amount,
            bool $paid,
            User $admin,
        ): void {
            $paidAt = $paid ? Carbon::parse('2026-10-21 09:00') : null;
            Upah::updateOrCreate(
                ['penugasan_id' => $assignment->id],
                [
                    'mode_perhitungan' => 'manual',
                    'tarif_per_satuan' => null,
                    'jumlah_satuan' => null,
                    'jumlah_upah' => $amount,
                    'status_pembayaran' => $paid ? 'sudah_dibayar' : 'belum_dibayar',
                    'metode_pembayaran' => $paid ? 'Transfer bank' : null,
                    'tanggal_pembayaran' => $paidAt?->toDateString(),
                    'waktu_pembayaran' => $paidAt,
                    'dibayar_oleh' => $paid ? $admin->id : null,
                    'catatan' => $paid ? 'Dibayarkan setelah verifikasi pekerjaan.' : 'Menunggu penyelesaian dan verifikasi tugas.',
                ],
            );
        };

        $paidAssignments = [];
        foreach ([
            ['schedule' => 'Panen Padi Petak Utara', 'member' => 'ANG-001'],
            ['schedule' => 'Panen Padi Petak Utara', 'member' => 'ANG-003'],
            ['schedule' => 'Penanaman Bibit Petak Selatan', 'member' => 'ANG-002'],
            ['schedule' => 'Penanaman Bibit Petak Selatan', 'member' => 'ANG-005'],
        ] as $details) {
            $completed = $createAssignment($schedules[$details['schedule']], $members[$details['member']], 'completed');
            $recordAttendance($completed, 'hadir', '07:00', '11:30', 'Pekerjaan selesai dan telah diperiksa.');
            $paidAssignments[] = $completed;
        }

        foreach ($paidAssignments as $completed) {
            $recordWage($completed, (float) $completed->jadwal->total_upah / 2, true, $admin);
        }

        $rejectedAssignment = $createAssignment(
            $schedules['Pengendalian Hama Blok Barat'],
            $members['ANG-004'],
            'rejected',
        );
        $recordAttendance($rejectedAssignment, 'tidak_hadir', '13:00', '16:00', 'Laporan ditolak: bukti pekerjaan tidak sesuai.');

        $replacementAssignment = $createAssignment(
            $schedules['Pengendalian Hama Blok Barat'],
            $members['ANG-006'],
            'completed',
            $members['ANG-004'],
            'hasil_perubahan',
        );
        $replacementAssignment->update(['catatan_perubahan' => 'Menggantikan anggota yang laporan pekerjaannya ditolak.']);
        $recordAttendance($replacementAssignment, 'hadir', '13:00', '16:00', 'Tugas pengganti selesai dan telah diperiksa.');
        $recordWage($replacementAssignment, 160000, false, $admin);

        $cancelledAssignment = $createAssignment(
            $schedules['Pembersihan Saluran Irigasi'],
            $members['ANG-007'],
            'cancelled',
        );
        $cancelledAssignment->update(['catatan_perubahan' => 'Kegiatan dibatalkan karena hujan deras dan saluran tidak aman.']);

        $waitingAssignment = $createAssignment($schedules['Pemupukan Blok Timur'], $members['ANG-002'], 'waiting_verification');
        $recordAttendance($waitingAssignment, 'hadir', '08:00', '10:30', 'Laporan pekerjaan dikirim dan menunggu verifikasi admin.');

        $inProgressAssignment = $createAssignment($schedules['Pemupukan Blok Timur'], $members['ANG-004'], 'in_progress');
        $recordAttendance($inProgressAssignment, 'hadir', '08:15', null, 'Sedang bekerja di lokasi.');

        $futureAssignment = $createAssignment($schedules['Persiapan Tanam Petak Utara'], $members['ANG-001'], 'assigned');
        $createAssignment($schedules['Panen Sayuran Petak Selatan'], $members['ANG-005'], 'assigned');

        PermintaanPerubahan::updateOrCreate(
            ['penugasan_id' => $futureAssignment->id],
            [
                'diajukan_oleh' => $members['ANG-001']->id,
                'jenis_permintaan' => 'sanggah',
                'target_penugasan_id' => null,
                'target_jadwal_id' => null,
                'alasan' => 'Mohon ditinjau karena ada keperluan keluarga pada hari kegiatan.',
                'status' => 'pending',
                'diproses_oleh' => null,
                'diproses_at' => null,
                'catatan_admin' => null,
            ],
        );
    }
}
