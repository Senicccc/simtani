<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'jenis_pekerjaan_id' => ['required', 'exists:jenis_pekerjaan,id'],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['nullable', 'date_format:H:i'],
            'waktu_selesai' => ['nullable', 'date_format:H:i', 'after_or_equal:waktu_mulai'],
            'jumlah_orang_dibutuhkan' => ['required', 'integer', 'min:1'],
            'jumlah_satuan' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:draft,scheduled,ongoing,completed,cancelled'],
            'metode_penjadwalan' => ['nullable', 'in:otomatis,manual'],
        ];
    }
}
