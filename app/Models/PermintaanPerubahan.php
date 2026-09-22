<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanPerubahan extends Model
{
    protected $table = 'permintaan_perubahan';

    protected $fillable = [
        'penugasan_id',
        'diajukan_oleh',
        'jenis_permintaan',
        'target_penugasan_id',
        'alasan',
        'status',
        'diproses_oleh',
        'diproses_at',
        'catatan_admin',
    ];

    protected $casts = [
        'diproses_at' => 'datetime',
    ];

    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(Penugasan::class, 'penugasan_id');
    }

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function targetPenugasan(): BelongsTo
    {
        return $this->belongsTo(Penugasan::class, 'target_penugasan_id');
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
