<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nomor_anggota',
        'nama_lengkap',
        'foto',
        'nomor_wa',
        'email',
        'password',
        'role',
        'status_aktif',
        'alamat',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status_aktif' => 'boolean',
        'password' => 'hashed',
    ];

    public function kompetensi(): HasMany
    {
        return $this->hasMany(KompetensiAnggota::class, 'anggota_id');
    }

    public function jadwalDibuat(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'created_by');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'anggota_id');
    }

    public function penugasanOriginal(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'original_anggota_id');
    }

    public function permintaanDiajukan(): HasMany
    {
        return $this->hasMany(PermintaanPerubahan::class, 'diajukan_oleh');
    }

    public function permintaanDiproses(): HasMany
    {
        return $this->hasMany(PermintaanPerubahan::class, 'diproses_oleh');
    }

    public function upahDibayar(): HasMany
    {
        return $this->hasMany(Upah::class, 'dibayar_oleh');
    }

    public function verifyPenugasan(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'verified_by');
    }

    public static function factory(): UserFactory
    {
        return UserFactory::new();
    }
}
