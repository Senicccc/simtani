<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\Presensi;
use App\Models\Upah;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $start = $request->input('tanggal_mulai');
        $end = $request->input('tanggal_selesai');
        $anggota = $request->input('anggota');

        $jadwalQuery = Jadwal::query();
        $taskQuery = Penugasan::query()->with(['jadwal.jenisPekerjaan', 'anggota']);
        $attendanceQuery = Presensi::query()->with(['penugasan.anggota', 'penugasan.jadwal']);
        $wageQuery = Upah::query()->with(['penugasan.anggota', 'penugasan.jadwal.jenisPekerjaan']);

        if ($start) {
            $jadwalQuery->whereDate('tanggal', '>=', $start);
            $taskQuery->whereHas('jadwal', fn ($q) => $q->whereDate('tanggal', '>=', $start));
            $attendanceQuery->whereHas('penugasan.jadwal', fn ($q) => $q->whereDate('tanggal', '>=', $start));
            $wageQuery->whereHas('penugasan.jadwal', fn ($q) => $q->whereDate('tanggal', '>=', $start));
        }

        if ($end) {
            $jadwalQuery->whereDate('tanggal', '<=', $end);
            $taskQuery->whereHas('jadwal', fn ($q) => $q->whereDate('tanggal', '<=', $end));
            $attendanceQuery->whereHas('penugasan.jadwal', fn ($q) => $q->whereDate('tanggal', '<=', $end));
            $wageQuery->whereHas('penugasan.jadwal', fn ($q) => $q->whereDate('tanggal', '<=', $end));
        }

        if ($anggota) {
            $taskQuery->where('anggota_id', $anggota);
            $attendanceQuery->whereHas('penugasan', fn ($q) => $q->where('anggota_id', $anggota));
            $wageQuery->whereHas('penugasan', fn ($q) => $q->where('anggota_id', $anggota));
        }

        $rekapJadwal = $jadwalQuery->get();
        $rekapTugas = $taskQuery->get();
        $rekapPresensi = $attendanceQuery->get();
        $rekapUpah = $wageQuery->get();

        return view('admin.laporan.index', compact('rekapJadwal', 'rekapTugas', 'rekapPresensi', 'rekapUpah', 'start', 'end', 'anggota'));
    }
}
