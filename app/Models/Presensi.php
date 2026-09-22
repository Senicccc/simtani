<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presensi extends Model
{
    protected $table = 'presensi';

    protected $fillable = [
        'penugasan_id',
        'waktu_check_in',
        'waktu_check_out',
        'status',
        'catatan',
    ];

    protected $casts = [
        'waktu_check_in' => 'datetime',
        'waktu_check_out' => 'datetime',
    ];

    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(Penugasan::class, 'penugasan_id');
    }
}
