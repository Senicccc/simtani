@extends('layouts.backend')
@section('title', 'Upah Saya')

@section('content')
<div class="page-head">
  <div>
    <h2>Upah Saya</h2>
    <p>Bagian upah dihitung rata setelah seluruh tugas selesai dan laporan disetujui admin.</p>
  </div>
</div>

<h3 class="section-title">Menunggu Pemeriksaan</h3>
<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Tanggal Laporan</th>
          <th>Kegiatan</th>
          <th>Upah yang Disepakati</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($menungguVerifikasi as $penugasan)
          <tr>
            <td class="cell-num">{{ $penugasan->completed_at?->format('d/m/Y') ?? '-' }}</td>
            <td><a href="{{ route('anggota.tugas.show', $penugasan) }}" class="table-action">{{ $penugasan->jadwal->nama_kegiatan }}</a></td>
            <td>
              Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }}
              <span class="cell-sub">total kegiatan, dibagi rata</span>
            </td>
            <td class="cell-reason muted">Menunggu pemeriksaan admin dan penyelesaian tugas anggota lain.</td>
          </tr>
        @empty
          <tr><td colspan="4"><div class="empty-state">Tidak ada laporan yang menunggu pemeriksaan.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<h3 class="section-title">Daftar Pembayaran</h3>
<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Tanggal Kegiatan</th>
          <th>Kegiatan</th>
          <th>Presensi</th>
          <th>Jumlah</th>
          <th>Status</th>
          <th>Dibayar</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($upah as $item)
          <tr>
            <td class="cell-num">{{ $item->penugasan->jadwal->tanggal?->format('d/m/Y') ?? '-' }}</td>
            <td><a href="{{ route('anggota.tugas.show', $item->penugasan) }}" class="table-action">{{ $item->penugasan->jadwal->nama_kegiatan ?? '-' }}</a></td>
            <td>@include('admin.partials.status-badge', ['type' => 'presensi', 'status' => $item->penugasan->presensi?->status ?? 'belum'])</td>
            <td class="cell-num">Rp {{ number_format((float) $item->jumlah_upah, 0, ',', '.') }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'bayar', 'status' => $item->status_pembayaran])</td>
            <td>
              @if ($item->metode_pembayaran || $item->waktu_pembayaran)
                <span class="cell-name">{{ $item->metode_pembayaran ?? '-' }}</span>
                <span class="cell-sub">{{ $item->waktu_pembayaran?->format('d/m/Y H:i') ?? '-' }}</span>
              @else
                -
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state">Belum ada upah yang siap dibayar.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection