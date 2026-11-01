<?php

namespace Database\Seeders;

use App\Models\JenisPekerjaan;
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
                'nama_lengkap' => 'Admin SIMTANI',
                'email' => 'admin1@simtani.test',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status_aktif' => true,
                'alamat' => 'Sekretariat Kelompok Tani Makmur',
                'nomor_wa' => '081234567890',
            ],
        );

        $members = [
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

        foreach ($members as $member) {
            User::updateOrCreate(
                ['nomor_anggota' => $member['nomor_anggota']],
                [
                    ...$member,
                    'password' => Hash::make('anggota123'),
                    'role' => 'anggota',
                    'alamat' => 'Desa Makmur, Kecamatan Sukamaju',
                ],
            );
        }

        $jobs = [
            'Penanaman' => ['deskripsi' => 'Persiapan lahan dan penanaman bibit sesuai jarak tanam.', 'satuan' => 'petak'],
            'Pemupukan' => ['deskripsi' => 'Pemupukan tanaman sesuai dosis dan jadwal kelompok.', 'satuan' => 'blok'],
            'Panen' => ['deskripsi' => 'Pemanenan, pemilahan, dan pencatatan hasil panen.', 'satuan' => 'karung'],
            'Pengendalian Hama' => ['deskripsi' => 'Pemeriksaan tanaman dan pengendalian hama terpadu.', 'satuan' => 'petak'],
            'Pembersihan Lahan' => ['deskripsi' => 'Pembersihan gulma dan saluran air lahan pertanian.', 'satuan' => 'hari'],
        ];

        foreach ($jobs as $name => $details) {
            JenisPekerjaan::updateOrCreate(
                ['nama_pekerjaan' => $name],
                [...$details, 'created_by' => $admin->id],
            );
        }

        if (app()->environment('local', 'testing')) {
            $this->call(SmartDemoSeeder::class);
        }
    }
}
