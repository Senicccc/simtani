<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\Presensi;
use App\Models\Upah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.laporan.index', $this->reportData($request));
    }

    public function export(Request $request): View
    {
        return view('admin.laporan.pdf', $this->reportData($request));
    }

    private function reportData(Request $request): array
    {
        $start = $request->input('tanggal_mulai');
        $end = $request->input('tanggal_selesai');
        $anggota = $request->input('anggota');
        $kategori = $request->input('kategori');
        $q = $request->input('q');
        $anggotaList = User::where('role', 'anggota')->orderBy('nama_lengkap')->get();
        $kategoriList = \App\Models\JenisPekerjaan::orderBy('nama_pekerjaan')->get();

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

        if ($kategori) {
            $jadwalQuery->where('jenis_pekerjaan_id', $kategori);
            $taskQuery->whereHas('jadwal', fn ($query) => $query->where('jenis_pekerjaan_id', $kategori));
            $attendanceQuery->whereHas('penugasan.jadwal', fn ($query) => $query->where('jenis_pekerjaan_id', $kategori));
            $wageQuery->whereHas('penugasan.jadwal', fn ($query) => $query->where('jenis_pekerjaan_id', $kategori));
        }

        if ($q) {
            $jadwalQuery->where('nama_kegiatan', 'like', '%'.$q.'%');
            $taskQuery->where(function ($query) use ($q) {
                $query->whereHas('jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$q.'%'))
                    ->orWhereHas('anggota', fn ($member) => $member->where('nama_lengkap', 'like', '%'.$q.'%'));
            });
            $attendanceQuery->whereHas('penugasan.jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$q.'%'));
            $wageQuery->whereHas('penugasan.jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$q.'%'));
        }

        $rekapJadwal = $jadwalQuery->with('jenisPekerjaan')->get();
        $rekapTugas = $taskQuery->get();
        $rekapPresensi = $attendanceQuery->get();
        $rekapUpah = $wageQuery->get();

        return compact('rekapJadwal', 'rekapTugas', 'rekapPresensi', 'rekapUpah', 'start', 'end', 'anggota', 'anggotaList', 'kategori', 'kategoriList', 'q');
    }
}
