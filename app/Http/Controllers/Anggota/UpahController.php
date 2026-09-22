<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Upah;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UpahController extends Controller
{
    public function index(): View
    {
        $upah = Upah::with(['penugasan.jadwal.jenisPekerjaan', 'penugasan.anggota'])
            ->whereHas('penugasan', fn ($query) => $query->where('anggota_id', Auth::id()))
            ->orderByDesc('created_at')
            ->get();

        return view('anggota.upah.index', compact('upah'));
    }
}
