@extends('layouts.backend')
@section('title', 'Upah')
@section('content')
<h2>Upah</h2>

@if($selectedSchedule)
  <h3>Upah kegiatan: {{ $selectedSchedule->nama_kegiatan }}</h3>
  <p>Daftar pembayaran untuk kegiatan ini.</p>
@elseif(request()->filled('jadwal_id'))
  <div class="banner">Kegiatan tidak ditemukan. Daftar berikut tidak difilter berdasarkan kegiatan.</div>
@endif

@if(!request()->filled('status_pembayaran'))
  <h3>{{ $selectedSchedule ? 'Laporan kegiatan yang perlu diperiksa' : 'Laporan yang perlu diperiksa' }}</h3>
  <p>Tugas yang menunggu persetujuan admin. Setelah disetujui, upah masuk ke daftar pembayaran.</p>
  <div class="table-scroll">
    <table>
      <thead><tr><th>Anggota</th><th>Tugas</th><th>Tanggal laporan</th><th>Upah disepakati</th><th>Aksi</th></tr></thead>
      <tbody>
        @forelse($menungguVerifikasi as $penugasan)
          <tr>
            <td>{{ $penugasan->anggota->nama_lengkap }}</td>
            <td>{{ $penugasan->jadwal->nama_kegiatan }}</td>
            <td>{{ $penugasan->completed_at?->format('d-m-Y') ?? '-' }}</td>
            <td>Total Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }} (dibagi rata)</td>
            <td><a href="{{ route('admin.penugasan.show', $penugasan) }}" class="table-action">Periksa laporan</a></td>
          </tr>
        @empty
          <tr><td colspan="5">Tidak ada tugas yang menunggu pemeriksaan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endif

<h3>Daftar pembayaran</h3>
<form method="get" style="display:flex;gap:10px;margin:16px 0;flex-wrap:wrap">
  <label for="wage-status">Filter pembayaran</label>
  <select id="wage-status" name="status_pembayaran">
    <option value="">Semua status</option>
    <option value="belum_dibayar" @selected(request('status_pembayaran') === 'belum_dibayar')>Belum dibayar</option>
    <option value="sudah_dibayar" @selected(request('status_pembayaran') === 'sudah_dibayar')>Sudah dibayar</option>
  </select>
  <button type="submit" class="btn-outline">Terapkan filter</button>
  <a href="{{ route('admin.upah.index') }}" class="btn-outline">Hapus filter</a>
</form>
<div class="table-scroll">
  <table>
    <thead><tr><th>Anggota</th><th>Kegiatan</th><th>Presensi</th><th>Jumlah</th><th>Status</th><th>Dibayar lewat</th><th>Dibayar pada</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse($upah as $item)
        <tr>
          <td>{{ $item->penugasan->anggota->nama_lengkap ?? '-' }}</td>
          <td><a href="{{ route('admin.penugasan.show', $item->penugasan) }}">{{ $item->penugasan->jadwal->nama_kegiatan ?? '-' }}</a></td>
          <td>{{ ucfirst(str_replace('_', ' ', $item->penugasan->presensi?->status ?? 'belum dicatat')) }}</td>
          <td>Rp {{ number_format((float) $item->jumlah_upah, 0, ',', '.') }}</td>
          <td>{{ $item->status_pembayaran === 'sudah_dibayar' ? 'Sudah dibayar' : 'Belum dibayar' }}</td>
          <td>{{ $item->metode_pembayaran ?? '-' }}</td>
          <td>{{ $item->waktu_pembayaran?->format('d-m-Y H:i:s') ?? '-' }}</td>
          <td>
            <a href="{{ route('admin.upah.edit', $item) }}">Edit pembayaran</a>
            @if($item->status_pembayaran === 'belum_dibayar')
              <form method="post" action="{{ route('admin.upah.mark-paid', $item) }}">@csrf<button type="submit">Tandai dibayar</button></form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8">
          @if($selectedSchedule && $menungguVerifikasi->isNotEmpty())
            Upah belum dibagikan karena laporan kegiatan ini masih menunggu pemeriksaan admin.
          @elseif($selectedSchedule)
            Belum ada pembayaran untuk kegiatan ini. Upah dibagikan setelah seluruh tugas selesai dan laporan disetujui.
          @else
            Belum ada data upah yang siap dibayar.
          @endif
        </td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
