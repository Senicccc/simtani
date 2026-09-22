<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPekerjaan extends Model
{
    use HasFactory;

    protected $table = 'jenis_pekerjaan';

    protected $fillable = [
        'nama_pekerjaan',
        'deskripsi',
        'satuan',
        'tarif_default',
        'status_aktif',
        'created_by',
    ];

    protected $casts = [
        'tarif_default' => 'decimal:2',
        'status_aktif' => 'boolean',
    ];

    public function kompetensi(): HasMany
    {
        return $this->hasMany(KompetensiAnggota::class, 'jenis_pekerjaan_id');
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'jenis_pekerjaan_id');
    }

    public function pembuat(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
