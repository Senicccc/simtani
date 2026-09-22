<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreAnggotaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('nomor_anggota')) {
            $this->merge(['nomor_anggota' => 'ANG-'.Str::upper(Str::random(8))]);
        }
    }

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
        ];
    }
}
