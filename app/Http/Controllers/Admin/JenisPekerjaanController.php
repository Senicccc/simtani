<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJenisPekerjaanRequest;
use App\Http\Requests\UpdateJenisPekerjaanRequest;
use App\Models\JenisPekerjaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JenisPekerjaanController extends Controller
{
    public function index(): View
    {
        $jenisPekerjaan = JenisPekerjaan::orderBy('nama_pekerjaan')->get();

        return view('admin.jenis-pekerjaan.index', compact('jenisPekerjaan'));
    }

    public function create(): View
    {
        return view('admin.jenis-pekerjaan.create');
    }

    public function store(StoreJenisPekerjaanRequest $request): RedirectResponse
    {
        JenisPekerjaan::create([
            'nama_pekerjaan' => $request->nama_pekerjaan,
            'deskripsi' => $request->deskripsi,
            'satuan' => $request->satuan,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.jenis-pekerjaan.index')->with('success', 'Jenis pekerjaan berhasil ditambahkan.');
    }

    public function edit(JenisPekerjaan $jenisPekerjaan): View
    {
        return view('admin.jenis-pekerjaan.edit', compact('jenisPekerjaan'));
    }

    public function update(UpdateJenisPekerjaanRequest $request, JenisPekerjaan $jenisPekerjaan): RedirectResponse
    {
        $jenisPekerjaan->update([
            'nama_pekerjaan' => $request->nama_pekerjaan,
            'deskripsi' => $request->deskripsi,
            'satuan' => $request->satuan,
        ]);

        return redirect()->route('admin.jenis-pekerjaan.index')->with('success', 'Jenis pekerjaan berhasil diperbarui.');
    }

    public function destroy(JenisPekerjaan $jenisPekerjaan): RedirectResponse
    {
        if ($jenisPekerjaan->jadwal()->exists() || $jenisPekerjaan->kompetensi()->exists()) {
            return redirect()->route('admin.jenis-pekerjaan.index')
                ->withErrors(['hapus' => 'Jenis pekerjaan tidak dapat dihapus karena masih digunakan pada jadwal atau kompetensi anggota.']);
        }

        $jenisPekerjaan->delete();

        return redirect()->route('admin.jenis-pekerjaan.index')->with('success', 'Jenis pekerjaan berhasil dihapus.');
    }
}
