<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenugasanController extends Controller
{
    public function index(Request $request): View
    {
        $jadwal = Jadwal::with(['jenisPekerjaan', 'penugasan.anggota'])
            ->when($request->filled('q'), fn ($query) => $query->where('nama_kegiatan', 'like', '%'.$request->q.'%')->orWhereHas('penugasan.anggota', fn ($member) => $member->where('nama_lengkap', 'like', '%'.$request->q.'%')))
            ->orderByDesc('tanggal')->get();

        return view('admin.penugasan.index', compact('jadwal'));
    }

    public function create(): View
    {
        $jadwal = Jadwal::with('jenisPekerjaan')->whereIn('status', ['draft', 'scheduled'])->orderBy('tanggal')->get();
        $anggota = User::where('role', 'anggota')->where('status_aktif', true)->orderBy('nama_lengkap')->get();

        return view('admin.penugasan.create', compact('jadwal', 'anggota'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jadwal_id' => ['required', 'exists:jadwal,id'],
            'anggota_id' => ['required', 'exists:users,id'],
        ]);
        $schedule = Jadwal::findOrFail($validated['jadwal_id']);
        $member = User::where('role', 'anggota')->where('status_aktif', true)->findOrFail($validated['anggota_id']);

        if (Penugasan::where('jadwal_id', $schedule->id)->where('anggota_id', $member->id)->where('status', '!=', 'cancelled')->exists()) {
            return back()->withInput()->withErrors(['anggota_id' => 'Anggota sudah ditugaskan pada kegiatan ini.']);
        }

        $conflict = Penugasan::where('anggota_id', $member->id)
            ->where('status', '!=', 'cancelled')
            ->whereHas('jadwal', fn ($query) => $query->whereDate('tanggal', $schedule->tanggal))
            ->exists();

        if ($conflict) {
            return back()->withInput()->withErrors(['anggota_id' => 'Anggota memiliki bentrok jadwal pada tanggal tersebut.']);
        }

        $penugasan = Penugasan::create([
            'jadwal_id' => $schedule->id,
            'anggota_id' => $member->id,
            'original_anggota_id' => $member->id,
            'status' => 'assigned',
            'sumber_penugasan' => 'manual',
            'assigned_at' => now(),
        ]);

        return redirect()->route('admin.penugasan.show', $penugasan)->with('success', 'Penugasan berhasil ditambahkan.');
    }

    public function show(Penugasan $penugasan): View
    {
        $penugasan->load(['jadwal.jenisPekerjaan', 'jadwal.penugasan.anggota', 'anggota', 'presensi', 'upah', 'originalAnggota']);

        return view('admin.penugasan.show', compact('penugasan'));
    }

    public function edit(Penugasan $penugasan): View
    {
        $anggota = User::where('role', 'anggota')->where('status_aktif', true)->get();

        return view('admin.penugasan.edit', compact('penugasan', 'anggota'));
    }

    public function update(
        Request $request,
        Penugasan $penugasan
    ): RedirectResponse {
        $validated = $request->validate([
            'anggota_id' => ['required', 'exists:users,id'],
            'status' => ['nullable', 'in:assigned,in_progress,waiting_verification,completed,cancelled'],
            'catatan_verifikasi' => ['nullable', 'string'],
        ]);

        $newMember = User::where('role', 'anggota')->findOrFail($validated['anggota_id']);

        if ($newMember->status_aktif !== true) {
            return back()->withErrors(['anggota_id' => 'Anggota tidak aktif.']);
        }


        $conflict = Penugasan::where('anggota_id', $newMember->id)
            ->where('id', '!=', $penugasan->id)
            ->where('status', '!=', 'cancelled')
            ->whereHas('jadwal', function ($query) use ($penugasan) {
                $query->whereDate('tanggal', $penugasan->jadwal->tanggal);
            })->exists();

        if ($conflict) {
            return back()->withErrors(['anggota_id' => 'Anggota memiliki bentrok jadwal.']);
        }

        $penugasan->update([
            'anggota_id' => $newMember->id,
            'sumber_penugasan' => 'manual',
            'status' => $validated['status'] ?? $penugasan->status,
            'catatan_verifikasi' => $validated['catatan_verifikasi'] ?? $penugasan->catatan_verifikasi,
        ]);

        return redirect()->route('admin.penugasan.show', $penugasan)->with('success', 'Penugasan berhasil diperbarui.');
    }

    public function verify(Penugasan $penugasan): RedirectResponse
    {
        if ($penugasan->status !== 'waiting_verification') {
            return back()->withErrors(['status' => 'Status tugas tidak menunggu verifikasi.']);
        }

        $penugasan->update([
            'status' => 'completed',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'catatan_verifikasi' => 'Diverifikasi oleh admin.',
        ]);

        return redirect()->route('admin.penugasan.show', $penugasan)->with('success', 'Tugas berhasil diverifikasi.');
    }
}
