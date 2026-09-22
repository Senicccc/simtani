<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'nomor_anggota' => ['required', 'string', Rule::unique('users', 'nomor_anggota')->ignore($this->route('anggota'))],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nomor_wa' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($this->route('anggota'))],
            'password' => ['nullable', 'string', 'min:6'],
            'status_aktif' => ['boolean'],
            'alamat' => ['nullable', 'string'],
            'kompetensi' => ['nullable', 'array'],
            'kompetensi.*.jenis_pekerjaan_id' => ['required_with:kompetensi', 'exists:jenis_pekerjaan,id'],
            'kompetensi.*.tingkat_kemampuan' => ['required_with:kompetensi', 'in:pemula,menengah,mahir'],
        ];
    }
}
