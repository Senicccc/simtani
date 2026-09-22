<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Penugasan extends Model
{
    protected $table = 'penugasan';

    protected $fillable = [
        'jadwal_id',
        'anggota_id',
        'original_anggota_id',
        'status',
        'sumber_penugasan',
        'assigned_at',
        'started_at',
        'completed_at',
        'catatan_penyelesaian',
        'bukti_selesai',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anggota_id');
    }

    public function originalAnggota(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_anggota_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function presensi(): HasOne
    {
        return $this->hasOne(Presensi::class, 'penugasan_id');
    }

    public function upah(): HasOne
    {
        return $this->hasOne(Upah::class, 'penugasan_id');
    }

    public function permintaanPerubahan(): HasMany
    {
        return $this->hasMany(PermintaanPerubahan::class, 'penugasan_id');
    }
}
