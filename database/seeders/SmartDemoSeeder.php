<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\KompetensiAnggota;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SmartDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Data demo hanya dapat dibuat di lingkungan local atau testing.');
        }

        mt_srand(20261102);

        $admin = User::query()->where('nomor_anggota', 'ADM-001')->firstOrFail();
        $jobs = JenisPekerjaan::query()->orderBy('id')->get();
        if ($jobs->isEmpty()) {
            throw new RuntimeException('Jenis pekerjaan harus tersedia sebelum data demo dibuat.');
        }

        $activities = [
            [
                'nama_kegiatan' => 'Panen cabai di petak utara',
                'jenis_pekerjaan' => 'Panen',
                'lokasi' => 'Lahan kelompok tani blok utara',
                'tanggal' => '2026-11-02',
                'waktu_mulai' => '06:00',
                'waktu_selesai' => '10:00',
                'jumlah_orang' => 4,
                'jumlah_satuan' => 6,
            ],
            [
                'nama_kegiatan' => 'Membersihkan saluran irigasi',
                'jenis_pekerjaan' => 'Pembersihan Lahan',
                'lokasi' => 'Saluran irigasi lahan bersama',
                'tanggal' => '2026-11-04',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '10:00',
                'jumlah_orang' => 3,
                'jumlah_satuan' => 1,
            ],
            [
                'nama_kegiatan' => 'Pemupukan tanaman padi',
                'jenis_pekerjaan' => 'Pemupukan',
                'lokasi' => 'Sawah kelompok tani blok timur',
                'tanggal' => '2026-11-06',
                'waktu_mulai' => '06:30',
                'waktu_selesai' => '09:30',
                'jumlah_orang' => 4,
                'jumlah_satuan' => 4,
            ],
            [
                'nama_kegiatan' => 'Menanam bibit cabai di petak selatan',
                'jenis_pekerjaan' => 'Penanaman',
                'lokasi' => 'Lahan kelompok tani blok selatan',
                'tanggal' => '2026-11-08',
                'waktu_mulai' => '07:00',
                'waktu_selesai' => '11:00',
                'jumlah_orang' => 5,
                'jumlah_satuan' => 3,
            ],
        ];
        $activityNames = array_column($activities, 'nama_kegiatan');
        $managedSchedules = Jadwal::query()->where(function ($query) use ($activityNames): void {
            $query->where('nama_kegiatan', 'like', 'Demo %')
                ->orWhereIn('nama_kegiatan', $activityNames);
        });

        if ((clone $managedSchedules)->whereHas('penugasan')->exists()) {
            throw new RuntimeException('Jadwal demo yang sudah memiliki penugasan tidak dapat dibuat ulang.');
        }

        $legacyMembers = User::query()
            ->where('role', 'anggota')
            ->where('nama_lengkap', 'like', 'Anggota Demo %')
            ->where('email', 'like', 'anggota.%@simtani.local')
            ->get();
        $legacyMemberIds = $legacyMembers->modelKeys();

        if ($legacyMemberIds !== []) {
            $legacyMembersHaveHistory = User::query()
                ->whereKey($legacyMemberIds)
                ->where(function ($query): void {
                    $query->whereHas('jadwalDibuat')
                        ->orWhereHas('penugasan')
                        ->orWhereHas('penugasanOriginal')
                        ->orWhereHas('permintaanDiajukan')
                        ->orWhereHas('permintaanDiproses')
                        ->orWhereHas('upahDibayar')
                        ->orWhereHas('verifyPenugasan');
                })
                ->exists();
            $legacyMembersCreatedJobs = DB::table('jenis_pekerjaan')
                ->whereIn('created_by', $legacyMemberIds)
                ->exists();
            $legacyMembersHaveRecommendations = Schema::hasTable('rekomendasi_penugasan_log')
                && DB::table('rekomendasi_penugasan_log')->whereIn('anggota_id', $legacyMemberIds)->exists();

            if ($legacyMembersHaveHistory || $legacyMembersCreatedJobs || $legacyMembersHaveRecommendations) {
                throw new RuntimeException('Anggota demo yang sudah memiliki riwayat tidak dapat dihapus.');
            }
        }

        $managedScheduleIds = (clone $managedSchedules)->pluck('id');
        if (Schema::hasTable('rekomendasi_penugasan_log')
            && DB::table('rekomendasi_penugasan_log')->whereIn('jadwal_id', $managedScheduleIds)->exists()) {
            throw new RuntimeException('Jadwal demo yang memiliki riwayat rekomendasi tidak dapat dibuat ulang.');
        }

        $managedSchedules->delete();
        if ($legacyMemberIds !== []) {
            KompetensiAnggota::query()->whereIn('anggota_id', $legacyMemberIds)->delete();
            User::query()->whereKey($legacyMemberIds)->delete();
        }

        $members = User::query()
            ->where('role', 'anggota')
            ->where('status_aktif', true)
            ->orderBy('id')
            ->get();

        foreach ($members as $member) {
            foreach ($jobs as $job) {
                $roll = mt_rand(1, 100);
                $level = match (true) {
                    $roll <= 30 => 'pemula',
                    $roll <= 75 => 'menengah',
                    default => 'mahir',
                };

                KompetensiAnggota::updateOrCreate(
                    ['anggota_id' => $member->id, 'jenis_pekerjaan_id' => $job->id],
                    ['tingkat_kemampuan' => $level],
                );
            }
        }

        foreach ($activities as $activity) {
            $job = $jobs->firstWhere('nama_pekerjaan', $activity['jenis_pekerjaan']);
            if ($job === null) {
                throw new RuntimeException('Jenis pekerjaan untuk kegiatan contoh tidak ditemukan.');
            }

            Jadwal::updateOrCreate(
                ['nama_kegiatan' => $activity['nama_kegiatan']],
                [
                    'jenis_pekerjaan_id' => $job->id,
                    'deskripsi' => $job->deskripsi,
                    'lokasi' => $activity['lokasi'],
                    'tanggal' => $activity['tanggal'],
                    'tanggal_selesai' => $activity['tanggal'],
                    'waktu_mulai' => $activity['waktu_mulai'],
                    'waktu_selesai' => $activity['waktu_selesai'],
                    'jumlah_orang_dibutuhkan' => $activity['jumlah_orang'],
                    'jumlah_satuan' => $activity['jumlah_satuan'],
                    'metode_penjadwalan' => 'manual',
                    'status' => 'scheduled',
                    'created_by' => $admin->id,
                ],
            );
        }
    }
}
