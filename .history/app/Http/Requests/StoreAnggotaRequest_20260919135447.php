<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'nomor_anggota' => ['required', 'string', 'unique:users,nomor_anggota'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'foto' => ['nullable', 'string'],
            'nomor_wa' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'status_aktif' => ['boolean'],
            'alamat' => ['nullable', 'string'],
            'kompetensi' => ['nullable', 'array'],
            'kompetensi.*.jenis_pekerjaan_id' => ['required_with:kompetensi', 'exists:jenis_pekerjaan,id'],
            'kompetensi.*.tingkat_kemampuan' => ['required_with:kompetensi', 'in:pemula,menengah,mahir'],
        ];
    }
}
