<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUpahRequest;
use App\Models\Jadwal;
use App\Models\Penugasan;
use App\Models\Upah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpahController extends Controller
{
    public function index(Request $request): View
    {
        $upah = Upah::with(['penugasan.anggota', 'penugasan.jadwal.jenisPekerjaan', 'penugasan.presensi'])
            ->when(in_array($request->input('status_pembayaran'), ['belum_dibayar', 'sudah_dibayar'], true), fn ($query) => $query->where('status_pembayaran', $request->input('status_pembayaran')))
            ->when($request->filled('jadwal_id'), fn ($query) => $query->whereHas('penugasan', fn ($assignment) => $assignment->where('jadwal_id', $request->integer('jadwal_id'))))
            ->orderByDesc('created_at')
            ->get();
        $menungguVerifikasi = Penugasan::with(['anggota', 'jadwal'])
            ->where('status', 'waiting_verification')
            ->when($request->filled('jadwal_id'), fn ($query) => $query->where('jadwal_id', $request->integer('jadwal_id')))
            ->orderByDesc('completed_at')
            ->get();
        $selectedSchedule = $request->filled('jadwal_id')
            ? Jadwal::find($request->integer('jadwal_id'))
            : null;

        return view('admin.upah.index', compact('upah', 'menungguVerifikasi', 'selectedSchedule'));
    }

    public function edit(Upah $upah): View
    {
        return view('admin.upah.edit', compact('upah'));
    }

    public function update(UpdateUpahRequest $request, Upah $upah): RedirectResponse
    {
        $isPaid = $request->status_pembayaran === 'sudah_dibayar';
        $paidAt = $isPaid ? now() : null;
        $upah->update([
            'status_pembayaran' => $request->status_pembayaran,
            'metode_pembayaran' => $request->metode_pembayaran,
            'tanggal_pembayaran' => $paidAt?->toDateString(),
            'waktu_pembayaran' => $paidAt,
            'dibayar_oleh' => $isPaid ? auth()->id() : null,
            'catatan' => $request->catatan,
        ]);

        return redirect()->route('admin.upah.index')->with('success', 'Data upah berhasil diperbarui.');
    }

    public function markPaid(Upah $upah): RedirectResponse
    {
        $upah->update([
            'status_pembayaran' => 'sudah_dibayar',
            'tanggal_pembayaran' => now()->toDateString(),
            'waktu_pembayaran' => now(),
            'dibayar_oleh' => auth()->id(),
        ]);

        return redirect()->route('admin.upah.index')->with('success', 'Pembayaran upah ditandai sudah dibayar.');
    }
}
