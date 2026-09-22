<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJenisPekerjaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'nama_pekerjaan' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'satuan' => ['nullable', 'string', 'max:100'],
            'tarif_default' => ['nullable', 'numeric', 'min:0'],
            'status_aktif' => ['boolean'],
        ];
    }
}
