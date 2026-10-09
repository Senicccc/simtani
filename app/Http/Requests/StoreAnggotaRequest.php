<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnggotaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('nomor_anggota')) {
            do {
                $memberNumber = 'ANG-'.strtoupper(bin2hex(random_bytes(4)));
            } while (User::where('nomor_anggota', $memberNumber)->exists());

            $this->merge(['nomor_anggota' => $memberNumber]);
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
