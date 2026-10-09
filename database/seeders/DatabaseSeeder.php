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
        $admin = User::updateOrCreate(
            ['nomor_anggota' => 'ADM-001'],
            [
                'nama_lengkap' => 'Bang David',
                'email' => 'admin.david@simtani.local',
                'password' => Hash::make('david12345'),
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
                'job' => 'Panen', 'date' => today()->subDays(7), 'start' => '07:00', 'end' => '12:00',
                'needed' => 2, 'quantity' => 3, 'wage' => 135000, 'status' => 'completed', 'members' => ['ANG-001', 'ANG-003'],
            ],
            'Pemupukan Blok Timur' => [
                'job' => 'Pemupukan', 'date' => today(), 'start' => '08:00', 'end' => '17:00',
                'needed' => 2, 'quantity' => 2, 'wage' => 280000, 'status' => 'ongoing', 'members' => ['ANG-002', 'ANG-004'],
            ],
            'Persiapan Tanam Musim Hujan' => [
                'job' => 'Penanaman', 'date' => today()->addDays(2), 'start' => '07:30', 'end' => '12:00',
                'needed' => 3, 'quantity' => 4, 'wage' => 700000, 'status' => 'scheduled', 'members' => ['ANG-001', 'ANG-005'],
            ],
            'Pengendalian Hama Lahan Selatan' => [
                'job' => 'Pengendalian Hama', 'date' => today()->addDays(3), 'start' => '13:00', 'end' => '16:00',
                'needed' => 2, 'quantity' => 2, 'wage' => 190000, 'status' => 'scheduled', 'members' => ['ANG-003', 'ANG-007'],
            ],
            'Pemupukan Petak Barat' => [
                'job' => 'Pemupukan', 'date' => today()->addDays(6), 'start' => '08:00', 'end' => '11:00',
                'needed' => 2, 'quantity' => 3, 'wage' => 420000, 'status' => 'scheduled', 'members' => [],
            ],
        ];

        $schedules = [];
        foreach ($scheduleData as $name => $details) {
            $schedules[$name] = Jadwal::updateOrCreate(
                ['nama_kegiatan' => $name],
                [
                    'jenis_pekerjaan_id' => $jobs[$details['job']]->id,
                    'deskripsi' => $jobData[$details['job']]['deskripsi'],
                    'lokasi' => 'Lahan Kelompok Tani Makmur',
                    'tanggal' => $details['date']->toDateString(),
                    'tanggal_selesai' => $details['date']->toDateString(),
                    'waktu_mulai' => $details['start'],
                    'waktu_selesai' => $details['end'],
                    'jumlah_orang_dibutuhkan' => $details['needed'],
                    'jumlah_satuan' => $details['quantity'],
                    'total_upah' => $details['wage'] * $details['needed'],
                    'perlu_verifikasi' => true,
                    'metode_penjadwalan' => 'manual',
                    'status' => $details['status'],
                    'created_by' => $admin->id,
                ],
            );
        }

        $assignment = static function (Jadwal $schedule, User $member, string $status) use ($admin): Penugasan {
            $startedAt = $schedule->tanggal->copy()->setTimeFromTimeString($schedule->waktu_mulai ?? '08:00');
            $completedAt = $schedule->tanggal->isToday()
                ? now()->subMinutes(30)
                : $schedule->tanggal->copy()->setTimeFromTimeString($schedule->waktu_selesai ?? '16:00');

            return Penugasan::updateOrCreate(
                ['jadwal_id' => $schedule->id, 'anggota_id' => $member->id],
                [
                    'original_anggota_id' => $member->id,
                    'status' => $status,
                    'sumber_penugasan' => 'manual',
                    'assigned_at' => $schedule->tanggal->copy()->subDays(2)->setTime(9, 0),
                    'started_at' => in_array($status, ['in_progress', 'waiting_verification', 'completed'], true) ? $startedAt : null,
                    'completed_at' => in_array($status, ['waiting_verification', 'completed'], true) ? $completedAt : null,
                    'verified_by' => $status === 'completed' ? $admin->id : null,
                    'verified_at' => $status === 'completed' ? $completedAt->copy()->addMinutes(15) : null,
                ],
            );
        };

        $completedAssignments = [];
        foreach (['ANG-001', 'ANG-003'] as $memberNumber) {
            $completedAssignments[] = $assignment($schedules['Panen Padi Petak Utara'], $members[$memberNumber], 'completed');
        }

        $attendanceStart = today()->subDays(7)->setTime(7, 0);
        $totalWageCents = (int) round((float) $schedules['Panen Padi Petak Utara']->total_upah * 100);
        $shareCents = intdiv($totalWageCents, count($completedAssignments));
        $remainderCents = $totalWageCents % count($completedAssignments);
        foreach ($completedAssignments as $index => $completedAssignment) {
            Presensi::updateOrCreate(
                ['penugasan_id' => $completedAssignment->id],
                [
                    'waktu_check_in' => $attendanceStart,
                    'waktu_check_out' => $attendanceStart->copy()->addHours(5),
                    'status' => 'hadir',
                    'catatan' => 'Hadir sampai pekerjaan selesai.',
                ],
            );

            $paidAt = $index === 0 ? today()->subDays(5)->setTime(15, 30) : null;
            Upah::updateOrCreate(
                ['penugasan_id' => $completedAssignment->id],
                [
                    'mode_perhitungan' => 'manual',
                    'tarif_per_satuan' => null,
                    'jumlah_satuan' => null,
                    'jumlah_upah' => ($shareCents + ($index < $remainderCents ? 1 : 0)) / 100,
                    'status_pembayaran' => $paidAt ? 'sudah_dibayar' : 'belum_dibayar',
                    'metode_pembayaran' => $paidAt ? 'Transfer bank' : null,
                    'tanggal_pembayaran' => $paidAt?->toDateString(),
                    'waktu_pembayaran' => $paidAt,
                    'dibayar_oleh' => $paidAt ? $admin->id : null,
                    'catatan' => 'Nominal ditetapkan saat tugas dibuat.',
                ],
            );
        }

        $waitingAssignment = $assignment($schedules['Pemupukan Blok Timur'], $members['ANG-002'], 'waiting_verification');
        Presensi::updateOrCreate(
            ['penugasan_id' => $waitingAssignment->id],
            [
                'waktu_check_in' => $waitingAssignment->started_at,
                'waktu_check_out' => $waitingAssignment->completed_at,
                'status' => 'hadir',
                'catatan' => 'Pekerjaan selesai, menunggu verifikasi admin.',
            ],
        );

        $inProgressAssignment = $assignment($schedules['Pemupukan Blok Timur'], $members['ANG-004'], 'in_progress');
        Presensi::updateOrCreate(
            ['penugasan_id' => $inProgressAssignment->id],
            [
                'waktu_check_in' => $inProgressAssignment->started_at,
                'status' => 'hadir',
                'catatan' => 'Sedang bekerja di lokasi.',
            ],
        );

        $exchangeSource = null;
        foreach ([
            ['schedule' => 'Persiapan Tanam Musim Hujan', 'member' => 'ANG-001'],
            ['schedule' => 'Persiapan Tanam Musim Hujan', 'member' => 'ANG-005'],
        ] as $details) {
            $scheduledAssignment = $assignment($schedules[$details['schedule']], $members[$details['member']], 'assigned');
            if ($details['member'] === 'ANG-001') {
                $exchangeSource = $scheduledAssignment;
            }
        }

        PermintaanPerubahan::updateOrCreate(
            ['penugasan_id' => $exchangeSource->id],
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
