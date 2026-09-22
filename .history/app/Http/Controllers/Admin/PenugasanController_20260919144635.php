<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisPekerjaan;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PenugasanController extends Controller
{
    public function index(): View
    {
        $penugasan = Penugasan::with(['jadwal.jenisPekerjaan', 'anggota'])->orderByDesc('assigned_at')->get();

        return view('admin.penugasan.index', compact('penugasan'));
    }

    public function show(Penugasan $penugasan): View
    {
        $penugasan->load(['jadwal.jenisPekerjaan', 'anggota', 'presensi', 'upah', 'originalAnggota']);

        return view('admin.penugasan.show', compact('penugasan'));
    }

    public function edit(Penugasan $penugasan): View
    {
        $anggota = User::where('role', 'anggota')->where('status_aktif', true)->get();

        return view('admin.penugasan.edit', compact('penugasan', 'anggota'));
    }

    public function update(
        \Illuminate\Http\Request $request,
        Penugasan $penugasan
    ): RedirectResponse {
        $validated = $request->validate([
            'anggota_id' => ['required', 'exists:users,id'],
            'status' => ['nullable', 'in:assigned,in_progress,waiting_verification,completed,cancelled'],
            'catatan_verifikasi' => ['nullable', 'string'],
        ]);

        $newMember = User::findOrFail($validated['anggota_id']);

        if ($newMember->status_aktif !== true) {
            return back()->withErrors(['anggota_id' => 'Anggota tidak aktif.']);
        }

        if (! $newMember->kompetensi()->where('jenis_pekerjaan_id', $penugasan->jadwal->jenis_pekerjaan_id)->exists()) {
            return back()->withErrors(['anggota_id' => 'Anggota tidak memiliki kompetensi yang sesuai.']);
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
