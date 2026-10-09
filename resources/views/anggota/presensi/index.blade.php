@extends('layouts.backend')
@section('title', 'Presensi Saya')
@section('content')
<h2>Riwayat Kehadiran</h2>
<p>Kehadiran dan izin dicatat terpisah untuk setiap tanggal kegiatan.</p>
<div class="table-scroll">
  <table>
    <thead><tr><th>Kegiatan</th><th>Mulai</th><th>Selesai</th><th>Status</th><th>Tugas</th></tr></thead>
    <tbody>
      @forelse($presensi as $item)
        <tr>
          <td>{{ $item->penugasan->jadwal->nama_kegiatan ?? '-' }}</td>
          <td>{{ $item->waktu_check_in?->format('d-m-Y H:i') ?? '-' }}</td>
          <td>{{ $item->waktu_check_out?->format('d-m-Y H:i') ?? '-' }}</td>
          <td>{{ $item->tanggal->format('d-m-Y') }} · {{ ['hadir' => 'Hadir', 'izin' => 'Izin', 'tidak_hadir' => 'Tidak hadir'][$item->status] ?? $item->status }}</td>
          <td><a href="{{ route('anggota.tugas.show', $item->penugasan) }}">Buka tugas</a></td>
        </tr>
      @empty
        <tr><td colspan="5">Belum ada penugasan.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
