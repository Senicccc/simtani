<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Penugasan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresensiController extends Controller
{
    public function index(): View
    {
        $presensi = Presensi::with(['penugasan.anggota', 'penugasan.jadwal'])
            ->when($request->filled('q'), fn ($query) => $query->whereHas('penugasan.anggota', fn ($member) => $member->where('nama_lengkap', 'like', '%'.$request->q.'%'))->orWhereHas('penugasan.jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$request->q.'%')))
            ->orderByDesc('created_at')->get();

        return view('admin.presensi.index', compact('presensi'));
    }

    public function create(): View
    {
        $penugasan = Penugasan::with(['anggota', 'jadwal'])
            ->whereDoesntHave('presensi')
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('assigned_at')->get();

        return view('admin.presensi.create', compact('penugasan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penugasan_id' => ['required', 'exists:penugasan,id', 'unique:presensi,penugasan_id'],
            'waktu_check_in' => ['nullable', 'date'],
            'waktu_check_out' => ['nullable', 'date', 'after_or_equal:waktu_check_in'],
            'status' => ['required', 'in:hadir,tidak_hadir,izin'],
            'catatan' => ['nullable', 'string'],
        ]);
        Presensi::create($validated);

        return redirect()->route('admin.presensi.index')->with('success', 'Presensi berhasil ditambahkan.');
    }

    public function edit(Presensi $presensi): View
    {
        $presensi->load(['penugasan.anggota', 'penugasan.jadwal']);

        return view('admin.presensi.edit', compact('presensi'));
    }

    public function update(Request $request, Presensi $presensi): RedirectResponse
    {
        $validated = $request->validate([
            'waktu_check_in' => ['nullable', 'date'],
            'waktu_check_out' => ['nullable', 'date', 'after_or_equal:waktu_check_in'],
            'status' => ['required', 'in:hadir,tidak_hadir,izin'],
            'catatan' => ['nullable', 'string'],
        ]);
        $presensi->update($validated);

        return redirect()->route('admin.presensi.index')->with('success', 'Presensi berhasil diperbarui.');
    }

    public function destroy(Presensi $presensi): RedirectResponse
    {
        $presensi->delete();

        return redirect()->route('admin.presensi.index')->with('success', 'Presensi berhasil dihapus.');
    }
}
