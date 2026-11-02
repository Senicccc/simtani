<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\KompetensiAnggota;
use App\Models\Penugasan;
use App\Models\Presensi;
use App\Models\User;
use App\Services\UpahTugasService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmartDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Data uji lapangan hanya dapat dibuat di lingkungan local atau testing.');
        }

        DB::transaction(function (): void {
            $admin = User::query()->where('nomor_anggota', 'ADM-001')->firstOrFail();
            $jobs = JenisPekerjaan::query()->get()->keyBy('nama_pekerjaan');
            $members = User::query()
                ->where('role', 'anggota')
                ->where('status_aktif', true)
                ->orderBy('nomor_anggota')
                ->get()
                ->keyBy('nomor_anggota');

            if ($jobs->count() !== 5 || $members->count() < 14) {
                throw new RuntimeException('Lima jenis pekerjaan dan sedikitnya 14 anggota aktif harus tersedia sebelum data uji dibuat.');
            }

            $levels = ['pemula', 'menengah', 'mahir'];
            foreach ($members->values() as $memberIndex => $member) {
                foreach ($jobs->values() as $jobIndex => $job) {
                    KompetensiAnggota::updateOrCreate(
                        ['anggota_id' => $member->id, 'jenis_pekerjaan_id' => $job->id],
                        ['tingkat_kemampuan' => $levels[($memberIndex + $jobIndex) % count($levels)]],
                    );
                }
            }

            foreach ($this->activities() as $activity) {
                $job = $jobs->get($activity['jenis_pekerjaan']);
                if ($job === null) {
                    throw new RuntimeException('Jenis pekerjaan untuk kegiatan uji tidak ditemukan: '.$activity['jenis_pekerjaan']);
                }

                $schedule = Jadwal::updateOrCreate(
                    ['nama_kegiatan' => $activity['nama_kegiatan']],
                    [
                        'jenis_pekerjaan_id' => $job->id,
                        'deskripsi' => $job->deskripsi,
                        'lokasi' => $activity['lokasi'],
                        'tanggal' => $activity['tanggal'],
                        'tanggal_selesai' => $activity['tanggal'],
                        'waktu_mulai' => $activity['waktu_mulai'],
                        'waktu_selesai' => $activity['waktu_selesai'],
                        'jumlah_orang_dibutuhkan' => $activity['jumlah_orang_dibutuhkan'] ?? count($activity['anggota']),
                        'jumlah_satuan' => $activity['jumlah_satuan'],
                        'total_upah' => 180000 * count($activity['anggota']),
                        'perlu_verifikasi' => true,
                        'metode_penjadwalan' => 'manual',
                        'status' => $activity['status'],
                        'created_by' => $admin->id,
                    ],
                );

                foreach ($activity['anggota'] as $memberNumber) {
                    $member = $members->get($memberNumber);
                    if ($member === null) {
                        throw new RuntimeException('Anggota untuk kegiatan uji tidak ditemukan: '.$memberNumber);
                    }

                    $assignmentData = $this->assignmentData($activity);
                    $assignment = Penugasan::updateOrCreate(
                        ['jadwal_id' => $schedule->id, 'anggota_id' => $member->id],
                        [
                            'original_anggota_id' => $member->id,
                            'tanggal_mulai' => $activity['tanggal'],
                            'tanggal_selesai' => $activity['tanggal'],
                            'status' => $assignmentData['status'],
                            'sumber_penugasan' => 'manual',
                            'assigned_at' => $activity['tanggal'].' '.$activity['waktu_mulai'].':00',
                            'started_at' => $assignmentData['started_at'],
                            'completed_at' => $assignmentData['completed_at'],
                            'catatan_penyelesaian' => $assignmentData['catatan_penyelesaian'],
                            'verified_by' => $assignmentData['verified_by'] ? $admin->id : null,
                            'verified_at' => $assignmentData['verified_at'],
                            'catatan_verifikasi' => $assignmentData['catatan_verifikasi'],
                        ],
                    );

                    if ($assignmentData['has_attendance']) {
                        Presensi::updateOrCreate(
                            ['penugasan_id' => $assignment->id],
                            [
                                'waktu_check_in' => $activity['tanggal'].' '.$activity['waktu_mulai'].':00',
                                'waktu_check_out' => $assignmentData['completed_at']
                                    ? $activity['tanggal'].' '.$activity['waktu_selesai'].':00'
                                    : null,
                                'status' => 'hadir',
                                'catatan' => 'Presensi tercatat untuk simulasi uji lapangan.',
                            ],
                        );
                    } else {
                        Presensi::query()
                            ->where('penugasan_id', $assignment->id)
                            ->delete();
                    }

                    if ($activity['kategori'] !== 'selesai') {
                        $assignment->upah()->delete();
                    }
                }

                if ($activity['kategori'] === 'selesai') {
                    app(UpahTugasService::class)->distributeForCompletedSchedule($schedule);
                }
            }
        });
    }

    /**
     * @return array{status: string, started_at: ?string, completed_at: ?string, catatan_penyelesaian: ?string, verified_by: bool, verified_at: ?string, catatan_verifikasi: ?string, has_attendance: bool}
     */
    private function assignmentData(array $activity): array
    {
        if ($activity['kategori'] === 'selesai') {
            return [
                'status' => 'completed',
                'started_at' => $activity['tanggal'].' '.$activity['waktu_mulai'].':00',
                'completed_at' => $activity['tanggal'].' '.$activity['waktu_selesai'].':00',
                'catatan_penyelesaian' => 'Kegiatan selesai dan siap ditinjau sebagai data uji.',
                'verified_by' => true,
                'verified_at' => $activity['tanggal'].' '.$activity['waktu_selesai'].':00',
                'catatan_verifikasi' => 'Laporan kegiatan disetujui.',
                'has_attendance' => true,
            ];
        }

        if ($activity['status_penugasan'] === 'waiting_verification') {
            return [
                'status' => 'waiting_verification',
                'started_at' => $activity['tanggal'].' '.$activity['waktu_mulai'].':00',
                'completed_at' => $activity['tanggal'].' '.$activity['waktu_selesai'].':00',
                'catatan_penyelesaian' => 'Panen selesai; laporan menunggu pemeriksaan admin.',
                'verified_by' => false,
                'verified_at' => null,
                'catatan_verifikasi' => null,
                'has_attendance' => true,
            ];
        }

        return [
            'status' => 'assigned',
            'started_at' => null,
            'completed_at' => null,
            'catatan_penyelesaian' => null,
            'verified_by' => false,
            'verified_at' => null,
            'catatan_verifikasi' => null,
            'has_attendance' => false,
        ];
    }

    /**
     * @return list<array{kategori: string, nama_kegiatan: string, jenis_pekerjaan: string, lokasi: string, tanggal: string, waktu_mulai: string, waktu_selesai: string, jumlah_satuan: int, anggota: list<string>, status: string, status_penugasan: ?string, jumlah_orang_dibutuhkan?: int}>
     */
    private function activities(): array
    {
        return [
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Pengendalian hama kebun sayur',
                'jenis_pekerjaan' => 'Pengendalian Hama',
                'lokasi' => 'Kebun sayur',
                'tanggal' => '2026-10-25',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '09:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-014', 'ANG-015'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Pembersihan petak pembibitan',
                'jenis_pekerjaan' => 'Pembersihan Lahan',
                'lokasi' => 'Petak pembibitan',
                'tanggal' => '2026-10-26',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '09:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-012'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Panen cabai petak utara',
                'jenis_pekerjaan' => 'Panen',
                'lokasi' => 'Petak utara',
                'tanggal' => '2026-10-28',
                'waktu_mulai' => '06:00',
                'waktu_selesai' => '09:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-001'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Pemupukan sawah blok timur',
                'jenis_pekerjaan' => 'Pemupukan',
                'lokasi' => 'Blok timur',
                'tanggal' => '2026-10-29',
                'waktu_mulai' => '06:30',
                'waktu_selesai' => '09:30',
                'jumlah_satuan' => 2,
                'anggota' => ['ANG-002', 'ANG-003'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Pembersihan saluran irigasi',
                'jenis_pekerjaan' => 'Pembersihan Lahan',
                'lokasi' => 'Saluran irigasi utama',
                'tanggal' => '2026-10-30',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '10:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-004', 'ANG-005', 'ANG-006'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Penanaman bibit cabai petak selatan',
                'jenis_pekerjaan' => 'Penanaman',
                'lokasi' => 'Petak selatan',
                'tanggal' => '2026-10-31',
                'waktu_mulai' => '06:00',
                'waktu_selesai' => '10:00',
                'jumlah_satuan' => 2,
                'anggota' => ['ANG-007', 'ANG-008', 'ANG-010', 'ANG-011'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Pemeriksaan hama tanaman padi',
                'jenis_pekerjaan' => 'Pengendalian Hama',
                'lokasi' => 'Sawah blok barat',
                'tanggal' => '2026-11-01',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '09:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-001'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'rentang',
                'nama_kegiatan' => 'Panen cabai petak tengah',
                'jenis_pekerjaan' => 'Panen',
                'lokasi' => 'Petak tengah',
                'tanggal' => '2026-11-02',
                'waktu_mulai' => '06:00',
                'waktu_selesai' => '09:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-001'],
                'status' => 'ongoing',
                'status_penugasan' => 'waiting_verification',
            ],
            [
                'kategori' => 'rentang',
                'nama_kegiatan' => 'Persiapan lahan tanam',
                'jenis_pekerjaan' => 'Pembersihan Lahan',
                'lokasi' => 'Petak selatan',
                'tanggal' => '2026-11-02',
                'waktu_mulai' => '14:00',
                'waktu_selesai' => '17:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-002', 'ANG-003'],
                'status' => 'ongoing',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'rentang',
                'nama_kegiatan' => 'Penyemaian bibit sayuran',
                'jenis_pekerjaan' => 'Penanaman',
                'lokasi' => 'Rumah bibit',
                'tanggal' => '2026-11-03',
                'waktu_mulai' => '06:00',
                'waktu_selesai' => '09:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-004', 'ANG-005', 'ANG-006'],
                'jumlah_orang_dibutuhkan' => 4,
                'status' => 'scheduled',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'rentang',
                'nama_kegiatan' => 'Pemupukan tanaman cabai',
                'jenis_pekerjaan' => 'Pemupukan',
                'lokasi' => 'Petak utara',
                'tanggal' => '2026-11-03',
                'waktu_mulai' => '10:00',
                'waktu_selesai' => '13:00',
                'jumlah_satuan' => 2,
                'anggota' => ['ANG-007', 'ANG-008', 'ANG-010', 'ANG-011'],
                'status' => 'scheduled',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Pemeriksaan tanaman padi',
                'jenis_pekerjaan' => 'Pengendalian Hama',
                'lokasi' => 'Sawah blok timur',
                'tanggal' => '2026-10-22',
                'waktu_mulai' => '06:00',
                'waktu_selesai' => '09:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-012'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Perawatan saluran air',
                'jenis_pekerjaan' => 'Pembersihan Lahan',
                'lokasi' => 'Saluran irigasi utama',
                'tanggal' => '2026-10-23',
                'waktu_mulai' => '10:00',
                'waktu_selesai' => '13:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-013', 'ANG-014'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'rentang',
                'nama_kegiatan' => 'Penanaman bibit tomat',
                'jenis_pekerjaan' => 'Penanaman',
                'lokasi' => 'Petak pembibitan',
                'tanggal' => '2026-11-05',
                'waktu_mulai' => '06:00',
                'waktu_selesai' => '09:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-001', 'ANG-002', 'ANG-003'],
                'status' => 'scheduled',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'selesai',
                'nama_kegiatan' => 'Pemupukan sawah blok selatan',
                'jenis_pekerjaan' => 'Pemupukan',
                'lokasi' => 'Blok selatan',
                'tanggal' => '2026-10-24',
                'waktu_mulai' => '06:00',
                'waktu_selesai' => '10:00',
                'jumlah_satuan' => 2,
                'anggota' => ['ANG-004', 'ANG-005', 'ANG-006', 'ANG-007'],
                'status' => 'completed',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'rentang',
                'nama_kegiatan' => 'Pengendalian hama tanaman cabai',
                'jenis_pekerjaan' => 'Pengendalian Hama',
                'lokasi' => 'Petak tengah',
                'tanggal' => '2026-11-07',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '10:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-008'],
                'status' => 'scheduled',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'rentang',
                'nama_kegiatan' => 'Pembersihan gulma petak utara',
                'jenis_pekerjaan' => 'Pembersihan Lahan',
                'lokasi' => 'Petak utara',
                'tanggal' => '2026-11-08',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '11:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-010', 'ANG-011'],
                'status' => 'scheduled',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'mendatang',
                'nama_kegiatan' => 'Panen tomat petak timur',
                'jenis_pekerjaan' => 'Panen',
                'lokasi' => 'Petak timur',
                'tanggal' => '2026-11-09',
                'waktu_mulai' => '06:00',
                'waktu_selesai' => '10:00',
                'jumlah_satuan' => 2,
                'anggota' => ['ANG-001', 'ANG-002', 'ANG-003', 'ANG-004'],
                'status' => 'scheduled',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'mendatang',
                'nama_kegiatan' => 'Pemeriksaan hama sayuran',
                'jenis_pekerjaan' => 'Pengendalian Hama',
                'lokasi' => 'Petak pembibitan',
                'tanggal' => '2026-11-11',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '10:00',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-005', 'ANG-006', 'ANG-007'],
                'status' => 'scheduled',
                'status_penugasan' => null,
            ],
            [
                'kategori' => 'mendatang',
                'nama_kegiatan' => 'Penyiapan lahan blok barat',
                'jenis_pekerjaan' => 'Pembersihan Lahan',
                'lokasi' => 'Blok barat',
                'tanggal' => '2026-11-13',
                'waktu_mulai' => '06:30',
                'waktu_selesai' => '09:30',
                'jumlah_satuan' => 1,
                'anggota' => ['ANG-008', 'ANG-010'],
                'status' => 'scheduled',
                'status_penugasan' => null,
            ],
        ];
    }
}
