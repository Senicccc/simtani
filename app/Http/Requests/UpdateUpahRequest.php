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
            'status_pembayaran' => ['required', 'in:belum_dibayar,sudah_dibayar'],
            'metode_pembayaran' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
