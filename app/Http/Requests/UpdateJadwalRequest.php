<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJadwalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $times = [];
        foreach (['waktu_mulai', 'waktu_selesai'] as $field) {
            $value = $this->input($field);
            if (is_string($value) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
                $times[$field] = substr($value, 0, 5);
            }
        }

        if ($times !== []) {
            $this->merge($times);
        }
    }

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
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal'],
            'waktu_mulai' => ['nullable', 'date_format:H:i'],
            'waktu_selesai' => ['nullable', 'date_format:H:i', 'after_or_equal:waktu_mulai'],
            'jumlah_orang_dibutuhkan' => ['required', 'integer', 'min:1'],
            'jumlah_satuan' => ['nullable', 'numeric', 'min:0'],
            'total_upah' => ['required', 'numeric', 'min:0.01'],
            'perlu_verifikasi' => ['required', 'boolean'],
            'status' => ['nullable', 'in:draft,scheduled,cancelled'],
            'metode_penjadwalan' => ['nullable', 'in:otomatis,manual'],
        ];
    }
}
