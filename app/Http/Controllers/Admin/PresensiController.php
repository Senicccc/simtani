<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresensiController extends Controller
{
    public function index(Request $request): View
    {
        $presensi = Presensi::with(['penugasan.anggota', 'penugasan.jadwal'])
            ->when($request->filled('q'), fn ($query) => $query->whereHas('penugasan.anggota', fn ($member) => $member->where('nama_lengkap', 'like', '%'.$request->q.'%'))->orWhereHas('penugasan.jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$request->q.'%')))
            ->orderByDesc('created_at')->get();

        return view('admin.presensi.index', compact('presensi'));
    }
}
