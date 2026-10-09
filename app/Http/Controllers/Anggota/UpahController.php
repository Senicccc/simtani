<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use App\Models\Upah;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UpahController extends Controller
{
    public function index(): View
    {
        $upah = Upah::with(['penugasan.jadwal.jenisPekerjaan', 'penugasan.anggota', 'penugasan.presensi'])
            ->whereHas('penugasan', fn ($query) => $query->where('anggota_id', Auth::id()))
            ->orderByDesc('created_at')
            ->get();
        $menungguVerifikasi = Penugasan::with('jadwal')
            ->where('anggota_id', Auth::id())
            ->where('status', 'waiting_verification')
            ->orderByDesc('completed_at')
            ->get();

        return view('anggota.upah.index', compact('upah', 'menungguVerifikasi'));
    }
}
