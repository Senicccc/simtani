@extends('layouts.backend')

@section('title', 'Riwayat Tugas')

@section('content')
<div class="view-header">
  <h2 style="margin:0">Riwayat Tugas</h2>
  <a href="{{ route('anggota.tugas.index') }}" class="btn-outline">Kembali ke tugas aktif</a>
</div>

<div class="table-scroll">
  <table>
    <thead><tr><th>Kegiatan</th><th>Selesai pada</th><th>Upah</th><th>Status pembayaran</th><th>Detail</th></tr></thead>
    <tbody>
      @forelse($penugasan as $item)
        <tr>
          <td>{{ $item->jadwal->nama_kegiatan }}</td>
          <td>{{ $item->completed_at?->format('d-m-Y H:i') ?? '-' }}</td>
          <td>{{ $item->upah ? 'Rp '.number_format((float) $item->upah->jumlah_upah, 0, ',', '.') : 'Belum tercatat' }}</td>
          <td>{{ $item->upah?->status_pembayaran === 'sudah_dibayar' ? 'Sudah dibayar' : 'Belum dibayar' }}</td>
          <td><a href="{{ route('anggota.tugas.show', $item) }}">Lihat tugas</a></td>
        </tr>
      @empty
        <tr><td colspan="5">Belum ada tugas selesai.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
