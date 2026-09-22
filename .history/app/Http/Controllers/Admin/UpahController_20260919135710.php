<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpahRequest;
use App\Http\Requests\UpdateUpahRequest;
use App\Models\Penugasan;
use App\Models\Upah;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UpahController extends Controller
{
    public function index(): View
    {
        $upah = Upah::with(['penugasan.anggota', 'penugasan.jadwal.jenisPekerjaan'])->orderByDesc('created_at')->get();

        return view('admin.upah.index', compact('upah'));
    }

    public function edit(Upah $upah): View
    {
        return view('admin.upah.edit', compact('upah'));
    }

    public function update(UpdateUpahRequest $request, Upah $upah): RedirectResponse
    {
        $upah->update([
            'mode_perhitungan' => $request->mode_perhitungan,
            'tarif_per_satuan' => $request->tarif_per_satuan,
            'jumlah_satuan' => $request->jumlah_satuan,
            'jumlah_upah' => $request->jumlah_upah,
            'status_pembayaran' => $request->status_pembayaran,
            'tanggal_pembayaran' => $request->tanggal_pembayaran,
            'dibayar_oleh' => auth()->id(),
            'catatan' => $request->catatan,
        ]);

        return redirect()->route('admin.upah.index')->with('success', 'Data upah berhasil diperbarui.');
    }

    public function markPaid(Upah $upah): RedirectResponse
    {
        $upah->update([
            'status_pembayaran' => 'sudah_dibayar',
            'tanggal_pembayaran' => now()->toDateString(),
            'dibayar_oleh' => auth()->id(),
        ]);

        return redirect()->route('admin.upah.index')->with('success', 'Pembayaran upah ditandai sudah dibayar.');
    }
}
