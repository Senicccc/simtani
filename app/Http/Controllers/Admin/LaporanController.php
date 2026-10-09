<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\JenisPekerjaan;
use App\Models\Penugasan;
use App\Models\Presensi;
use App\Models\Upah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function exportCsv(Request $request): StreamedResponse
    {
        $data = $this->reportData($request);

        return response()->streamDownload(function () use ($data): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka aliran ekspor laporan.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            $writeRow = static function (array $row) use ($output): void {
                fputcsv($output, array_map(static function (mixed $value): string {
                    $value = (string) ($value ?? '');

                    return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
                }, $row), ',', '"', '');
            };

            $writeRow(['JADWAL']);
            $writeRow(['Tanggal', 'Kegiatan', 'Kategori', 'Lokasi', 'Status']);
            foreach ($data['rekapJadwal'] as $item) {
                $writeRow([$item->tanggal?->format('Y-m-d'), $item->nama_kegiatan, $item->jenisPekerjaan->nama_pekerjaan ?? '-', $item->lokasi, $item->status]);
            }

            $writeRow([]);
            $writeRow(['PENUGASAN']);
            $writeRow(['Tanggal', 'Kegiatan', 'Anggota', 'Status', 'Sumber']);
            foreach ($data['rekapTugas'] as $item) {
                $writeRow([$item->jadwal->tanggal?->format('Y-m-d'), $item->jadwal->nama_kegiatan, $item->anggota->nama_lengkap, $item->status, $item->sumber_penugasan]);
            }

            $writeRow([]);
            $writeRow(['PRESENSI']);
            $writeRow(['Tanggal', 'Kegiatan', 'Anggota', 'Check-in', 'Check-out', 'Status', 'Catatan']);
            foreach ($data['rekapPresensi'] as $item) {
                $writeRow([
                    $item->penugasan->jadwal->tanggal?->format('Y-m-d'),
                    $item->penugasan->jadwal->nama_kegiatan,
                    $item->penugasan->anggota->nama_lengkap,
                    $item->waktu_check_in?->format('Y-m-d H:i:s'),
                    $item->waktu_check_out?->format('Y-m-d H:i:s'),
                    $item->status,
                    $item->catatan,
                ]);
            }

            $writeRow([]);
            $writeRow(['UPAH']);
            $writeRow(['Tanggal', 'Kegiatan', 'Anggota', 'Jumlah Upah', 'Status Pembayaran', 'Metode Pembayaran', 'Tanggal Pembayaran']);
            foreach ($data['rekapUpah'] as $item) {
                $writeRow([
                    $item->penugasan->jadwal->tanggal?->format('Y-m-d'),
                    $item->penugasan->jadwal->nama_kegiatan,
                    $item->penugasan->anggota->nama_lengkap,
                    $item->jumlah_upah,
                    $item->status_pembayaran,
                    $item->metode_pembayaran,
                    $item->tanggal_pembayaran?->format('Y-m-d'),
                ]);
            }

            fclose($output);
        }, 'simtani-laporan-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function reportData(Request $request): array
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_selesai' => [
                'nullable',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if ($value && $request->filled('tanggal_mulai') && $value < $request->input('tanggal_mulai')) {
                        $fail('Tanggal selesai harus sama atau setelah tanggal mulai.');
                    }
                },
            ],
            'anggota' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'anggota')],
            'kategori' => ['nullable', 'integer', 'exists:jenis_pekerjaan,id'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);
        $start = $validated['tanggal_mulai'] ?? null;
        $end = $validated['tanggal_selesai'] ?? null;
        $anggota = $validated['anggota'] ?? null;
        $kategori = $validated['kategori'] ?? null;
        $q = $validated['q'] ?? null;
        $anggotaList = User::where('role', 'anggota')->orderBy('nama_lengkap')->get();
        $kategoriList = JenisPekerjaan::orderBy('nama_pekerjaan')->get();

        $jadwalQuery = Jadwal::query();
        $taskQuery = Penugasan::query()->with(['jadwal.jenisPekerjaan', 'anggota', 'presensi', 'upah']);
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
            $taskQuery->where(fn ($query) => $query
                ->whereHas('jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$q.'%'))
                ->orWhereHas('anggota', fn ($member) => $member->where('nama_lengkap', 'like', '%'.$q.'%')));
            $attendanceQuery->whereHas('penugasan', fn ($task) => $task
                ->whereHas('jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$q.'%'))
                ->orWhereHas('anggota', fn ($member) => $member->where('nama_lengkap', 'like', '%'.$q.'%')));
            $wageQuery->whereHas('penugasan', fn ($task) => $task
                ->whereHas('jadwal', fn ($schedule) => $schedule->where('nama_kegiatan', 'like', '%'.$q.'%'))
                ->orWhereHas('anggota', fn ($member) => $member->where('nama_lengkap', 'like', '%'.$q.'%')));
        }

        $rekapJadwal = $jadwalQuery->with('jenisPekerjaan')->get();
        $rekapTugas = $taskQuery->get();
        $rekapPresensi = $attendanceQuery->get();
        $rekapUpah = $wageQuery->get();

        return compact('rekapJadwal', 'rekapTugas', 'rekapPresensi', 'rekapUpah', 'start', 'end', 'anggota', 'anggotaList', 'kategori', 'kategoriList', 'q');
    }
}
