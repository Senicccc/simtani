<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUpahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'mode_perhitungan' => ['required', 'in:otomatis,manual'],
            'tarif_per_satuan' => ['nullable', 'numeric', 'min:0'],
            'jumlah_satuan' => ['nullable', 'numeric', 'min:0'],
            'jumlah_upah' => ['required', 'numeric', 'min:0'],
            'status_pembayaran' => ['required', 'in:belum_dibayar,sudah_dibayar'],
            'tanggal_pembayaran' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
