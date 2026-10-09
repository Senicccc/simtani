@extends('layouts.backend')
@section('title', 'Upah Saya')
@section('content')
<h2>Upah Saya</h2>
<h3>Menunggu pemeriksaan</h3>
<p>Bagian upah dihitung rata setelah seluruh tugas selesai dan laporan disetujui admin.</p>
<div class="table-scroll">
  <table>
    <thead><tr><th>Tanggal laporan</th><th>Kegiatan</th><th>Upah yang disepakati</th><th>Status</th></tr></thead>
    <tbody>
      @forelse($menungguVerifikasi as $penugasan)
        <tr>
          <td>{{ $penugasan->completed_at?->format('d-m-Y') ?? '-' }}</td>
          <td><a href="{{ route('anggota.tugas.show', $penugasan) }}">{{ $penugasan->jadwal->nama_kegiatan }}</a></td>
          <td>Total Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }} (dibagi rata)</td>
          <td>Menunggu pemeriksaan admin dan penyelesaian tugas anggota lain.</td>
        </tr>
      @empty
        <tr><td colspan="4">Tidak ada laporan yang menunggu pemeriksaan.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<h3>Daftar pembayaran</h3>
<div class="table-scroll">
  <table>
    <thead><tr><th>Tanggal kegiatan</th><th>Kegiatan</th><th>Presensi</th><th>Jumlah</th><th>Status</th><th>Dibayar pada</th><th>Dibayar lewat</th></tr></thead>
    <tbody>
      @forelse($upah as $item)
        <tr>
          <td>{{ $item->penugasan->jadwal->tanggal?->format('d-m-Y') ?? '-' }}</td>
          <td><a href="{{ route('anggota.tugas.show', $item->penugasan) }}">{{ $item->penugasan->jadwal->nama_kegiatan ?? '-' }}</a></td>
          <td>{{ ['hadir' => 'Hadir', 'izin' => 'Izin', 'tidak_hadir' => 'Tidak hadir'][$item->penugasan->presensi?->status] ?? 'Belum dicatat' }}</td>
          <td>Rp {{ number_format((float) $item->jumlah_upah, 0, ',', '.') }}</td>
          <td>{{ $item->status_pembayaran === 'sudah_dibayar' ? 'Sudah dibayar' : 'Belum dibayar' }}</td>
          <td>{{ $item->waktu_pembayaran?->format('d-m-Y H:i:s') ?? '-' }}</td>
          <td>{{ $item->metode_pembayaran ?? '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="7">Belum ada upah yang siap dibayar.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
