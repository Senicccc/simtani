<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KompetensiAnggota extends Model
{
    protected $table = 'kompetensi_anggota';

    protected $fillable = [
        'anggota_id',
        'jenis_pekerjaan_id',
        'tingkat_kemampuan',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anggota_id');
    }

    public function jenisPekerjaan(): BelongsTo
    {
        return $this->belongsTo(JenisPekerjaan::class, 'jenis_pekerjaan_id');
    }
}
