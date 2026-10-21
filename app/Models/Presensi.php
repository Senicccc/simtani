<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presensi extends Model
{
    protected $table = 'presensi';

    protected $fillable = [
        'penugasan_id',
        'tanggal',
        'waktu_check_in',
        'waktu_check_out',
        'status',
        'coverage_accepted',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_check_in' => 'datetime',
        'waktu_check_out' => 'datetime',
        'coverage_accepted' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Presensi $presensi): void {
            if (! $presensi->tanggal && $presensi->penugasan_id) {
                $presensi->tanggal = Penugasan::query()
                    ->with('jadwal')
                    ->findOrFail($presensi->penugasan_id)
                    ->tanggalMulaiEfektif();
            }
        });
    }

    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(Penugasan::class, 'penugasan_id');
    }
}
