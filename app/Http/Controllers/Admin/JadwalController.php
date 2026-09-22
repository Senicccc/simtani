<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJadwalRequest;
use App\Http\Requests\UpdateJadwalRequest;
use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Services\AutoScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function index(): View
    {
        $jadwal = Jadwal::with(['jenisPekerjaan', 'penugasan'])->orderBy('tanggal', 'desc')->get();

        return view('admin.jadwal.index', compact('jadwal'));
    }

    public function create(): View
    {
        $jenisPekerjaan = JenisPekerjaan::where('status_aktif', true)->get();

        return view('admin.jadwal.create', compact('jenisPekerjaan'));
    }

    public function store(StoreJadwalRequest $request): RedirectResponse
    {
        $jadwal = Jadwal::create([
            'jenis_pekerjaan_id' => $request->jenis_pekerjaan_id,
            'nama_kegiatan' => $request->nama_kegiatan,
            'deskripsi' => $request->deskripsi,
            'lokasi' => $request->lokasi,
            'tanggal' => $request->tanggal,
            'waktu_mulai' => $request->waktu_mulai,
            'waktu_selesai' => $request->waktu_selesai,
            'jumlah_orang_dibutuhkan' => $request->jumlah_orang_dibutuhkan,
            'jumlah_satuan' => $request->jumlah_satuan,
            'metode_penjadwalan' => 'manual',
            'status' => 'scheduled',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.jadwal.show', $jadwal)->with('success', 'Jadwal berhasil dibuat.');
    }

    public function show(Jadwal $jadwal): View
    {
        $jadwal->load(['jenisPekerjaan', 'penugasan.anggota']);

        return view('admin.jadwal.show', compact('jadwal'));
    }

    public function edit(Jadwal $jadwal): View
    {
        $jenisPekerjaan = JenisPekerjaan::where('status_aktif', true)->get();

        return view('admin.jadwal.edit', compact('jadwal', 'jenisPekerjaan'));
    }

    public function update(UpdateJadwalRequest $request, Jadwal $jadwal): RedirectResponse
    {
        $jadwal->update([
            'jenis_pekerjaan_id' => $request->jenis_pekerjaan_id,
            'nama_kegiatan' => $request->nama_kegiatan,
            'deskripsi' => $request->deskripsi,
            'lokasi' => $request->lokasi,
            'tanggal' => $request->tanggal,
            'waktu_mulai' => $request->waktu_mulai,
            'waktu_selesai' => $request->waktu_selesai,
            'jumlah_orang_dibutuhkan' => $request->jumlah_orang_dibutuhkan,
            'jumlah_satuan' => $request->jumlah_satuan,
            'metode_penjadwalan' => $request->metode_penjadwalan ?? $jadwal->metode_penjadwalan,
            'status' => $request->status ?? $jadwal->status,
        ]);

        return redirect()->route('admin.jadwal.show', $jadwal)->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function generate(AutoScheduleService $service, Jadwal $jadwal): RedirectResponse
    {
        try {
            $selected = $service->generateForSchedule($jadwal);
            $jadwal->update(['metode_penjadwalan' => 'otomatis', 'status' => 'scheduled']);

            return redirect()->route('admin.jadwal.penugasan', $jadwal)->with('success', 'Jadwal otomatis berhasil dibuat untuk '.count($selected).' anggota.');
        } catch (\Throwable $e) {
            return back()->withErrors(['jadwal' => $e->getMessage()]);
        }
    }

    public function penugasan(Jadwal $jadwal): View
    {
        $jadwal->load(['penugasan.anggota', 'jenisPekerjaan']);

        return view('admin.jadwal.penugasan', compact('jadwal'));
    }
}
