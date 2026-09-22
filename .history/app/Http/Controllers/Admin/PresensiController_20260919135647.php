<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use Illuminate\View\View;

class PresensiController extends Controller
{
    public function index(): View
    {
        $presensi = Presensi::with(['penugasan.anggota', 'penugasan.jadwal'])->orderByDesc('created_at')->get();

        return view('admin.presensi.index', compact('presensi'));
    }
}
