<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermintaanPerubahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermintaanPerubahanController extends Controller
{
    public function index(): View
    {
        $permintaan = PermintaanPerubahan::with(['penugasan.jadwal', 'diajukanOleh', 'targetPenugasan'])->orderByDesc('created_at')->get();

        return view('admin.permintaan-perubahan.index', compact('permintaan'));
    }

    public function show(PermintaanPerubahan $permintaan): View
    {
        $permintaan->load(['penugasan.jadwal', 'diajukanOleh', 'targetPenugasan.anggota']);

        return view('admin.permintaan-perubahan.show', compact('permintaan'));
    }

    public function process(PermintaanPerubahan $permintaan): RedirectResponse
    {
        $request = request();
        $action = $request->input('action');
        $status = $action === 'approve' ? 'approved' : 'rejected';

        if ($permintaan->jenis_permintaan === 'tukar' && $status === 'approved') {
            DB::transaction(function () use ($permintaan) {
                $source = $permintaan->penugasan;
                $target = $permintaan->targetPenugasan;

                if (! $target) {
                    throw new \RuntimeException('Target penugasan tidak valid.');
                }

                [$source->anggota_id, $target->anggota_id] = [$target->anggota_id, $source->anggota_id];
                $source->sumber_penugasan = 'hasil_perubahan';
                $target->sumber_penugasan = 'hasil_perubahan';
                $source->save();
                $target->save();
            });
        }

        $permintaan->update([
            'status' => $status,
            'diproses_oleh' => auth()->id(),
            'diproses_at' => now(),
            'catatan_admin' => $request->input('catatan_admin'),
        ]);

        return redirect()->route('admin.permintaan-perubahan.index')->with('success', 'Permintaan perubahan diproses.');
    }
}
