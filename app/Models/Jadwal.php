<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected $fillable = [
        'jenis_pekerjaan_id',
        'nama_kegiatan',
        'deskripsi',
        'lokasi',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'jumlah_orang_dibutuhkan',
        'jumlah_satuan',
        'metode_penjadwalan',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_satuan' => 'decimal:2',
    ];

    public function jenisPekerjaan(): BelongsTo
    {
        return $this->belongsTo(JenisPekerjaan::class, 'jenis_pekerjaan_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'jadwal_id');
    }
}
