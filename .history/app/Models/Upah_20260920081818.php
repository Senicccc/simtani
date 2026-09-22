<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Upah extends Model
{
    protected $table = 'upah';

    protected $fillable = [
        'penugasan_id',
        'mode_perhitungan',
        'tarif_per_satuan',
        'jumlah_satuan',
        'jumlah_upah',
        'status_pembayaran',
        'metode_pembayaran',
        'tanggal_pembayaran',
        'dibayar_oleh',
        'catatan',
    ];

    protected $casts = [
        'tarif_per_satuan' => 'decimal:2',
        'jumlah_satuan' => 'decimal:2',
        'jumlah_upah' => 'decimal:2',
        'tanggal_pembayaran' => 'date',
    ];

    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(Penugasan::class, 'penugasan_id');
    }

    public function pembayar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_oleh');
    }
}
