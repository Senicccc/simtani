@extends('layouts.backend')

@section('title', 'Laporan')

@section('content')
<div class="view-header">
  <h2 style="margin:0">Laporan Operasional</h2>
  <div>
    <a href="{{ route('admin.laporan.export.csv', request()->query()) }}" class="btn-outline">Unduh CSV</a>
    <a href="{{ route('admin.laporan.export', request()->query()) }}" class="btn-outline">Cetak / PDF</a>
  </div>
</div>

<form method="get" class="stat-card" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin:16px 0">
  <label>Mulai
    <input type="date" name="tanggal_mulai" value="{{ $start }}">
  </label>
  <label>Selesai
    <input type="date" name="tanggal_selesai" value="{{ $end }}">
  </label>
  <label>Kategori pekerjaan
    <select name="kategori">
      <option value="">Semua kategori</option>
      @foreach($kategoriList as $item)
        <option value="{{ $item->id }}" @selected((string) $kategori === (string) $item->id)>{{ $item->nama_pekerjaan }}</option>
      @endforeach
    </select>
  </label>
  <label>Anggota
    <select name="anggota">
      <option value="">Semua anggota</option>
      @foreach($anggotaList as $item)
        <option value="{{ $item->id }}" @selected((string) $anggota === (string) $item->id)>{{ $item->nama_lengkap }}</option>
      @endforeach
    </select>
  </label>
  <label>Nama kegiatan / anggota
    <input name="q" value="{{ $q }}" placeholder="Cari nama">
  </label>
  <button type="submit" class="btn-outline">Terapkan filter</button>
  <a href="{{ route('admin.laporan.index') }}" class="table-action">Bersihkan</a>
</form>

<div class="stat-row">
  <div class="stat-card"><div class="k">Jadwal ditemukan</div><div class="v">{{ $rekapJadwal->count() }}</div></div>
  <div class="stat-card"><div class="k">Penugasan selesai</div><div class="v">{{ $rekapTugas->where('status', 'completed')->count() }}</div></div>
  <div class="stat-card"><div class="k">Presensi hadir</div><div class="v">{{ $rekapPresensi->where('status', 'hadir')->count() }} / {{ $rekapPresensi->count() }}</div></div>
  <div class="stat-card"><div class="k">Upah belum dibayar</div><div class="v">Rp {{ number_format((float) $rekapUpah->where('status_pembayaran', 'belum_dibayar')->sum('jumlah_upah'), 0, ',', '.') }}</div></div>
</div>

<h3>Jadwal kegiatan</h3>
<div class="table-scroll">
  <table>
    <thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Jenis pekerjaan</th><th>Lokasi</th><th>Status</th><th>Penugasan</th></tr></thead>
    <tbody>
      @forelse($rekapJadwal as $item)
        <tr>
          <td>{{ $item->tanggal?->format('d-m-Y') }}</td>
          <td>{{ $item->nama_kegiatan }}</td>
          <td>{{ $item->jenisPekerjaan->nama_pekerjaan ?? '-' }}</td>
          <td>{{ $item->lokasi }}</td>
          <td>{{ ['draft' => 'Draf', 'scheduled' => 'Terjadwal', 'ongoing' => 'Sedang berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td>
          <td><a href="{{ route('admin.jadwal.penugasan', $item) }}" class="table-action">Lihat anggota</a></td>
        </tr>
      @empty
        <tr><td colspan="6">Tidak ada jadwal yang cocok dengan filter.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<h3>Penugasan dan hasil kerja</h3>
<div class="table-scroll">
  <table>
    <thead><tr><th>Kegiatan</th><th>Anggota</th><th>Status</th><th>Presensi</th><th>Upah</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse($rekapTugas as $item)
        <tr>
          <td>{{ $item->jadwal->nama_kegiatan }}<br><small>{{ $item->jadwal->tanggal?->format('d-m-Y') }}</small></td>
          <td>{{ $item->anggota->nama_lengkap }}</td>
          <td>{{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td>
          <td>{{ ucfirst(str_replace('_', ' ', $item->presensi?->status ?? 'belum dicatat')) }}</td>
          <td>{{ $item->upah ? 'Rp '.number_format((float) $item->upah->jumlah_upah, 0, ',', '.') : 'Belum dihitung' }}</td>
          <td><a href="{{ route('admin.penugasan.show', $item) }}" class="table-action">Detail tugas</a></td>
        </tr>
      @empty
        <tr><td colspan="6">Tidak ada penugasan yang cocok dengan filter.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<h3>Rincian presensi</h3>
<div class="table-scroll">
  <table>
    <thead><tr><th>Kegiatan</th><th>Anggota</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Catatan</th></tr></thead>
    <tbody>
      @forelse($rekapPresensi as $item)
        <tr>
          <td>{{ $item->penugasan->jadwal->nama_kegiatan }}</td>
          <td>{{ $item->penugasan->anggota->nama_lengkap }}</td>
          <td>{{ $item->waktu_check_in?->format('d-m-Y H:i') ?? '-' }}</td>
          <td>{{ $item->waktu_check_out?->format('d-m-Y H:i') ?? '-' }}</td>
          <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
          <td>{{ $item->catatan ?: '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="6">Belum ada presensi pada hasil filter.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<h3>Rincian upah</h3>
<div class="table-scroll">
  <table>
    <thead><tr><th>Kegiatan</th><th>Anggota</th><th>Upah disepakati</th><th>Status pembayaran</th><th>Pembayaran</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse($rekapUpah as $item)
        <tr>
          <td>{{ $item->penugasan->jadwal->nama_kegiatan }}</td>
          <td>{{ $item->penugasan->anggota->nama_lengkap }}</td>
          <td>Rp {{ number_format((float) $item->jumlah_upah, 0, ',', '.') }}</td>
          <td>{{ $item->status_pembayaran === 'sudah_dibayar' ? 'Sudah dibayar' : 'Belum dibayar' }}</td>
          <td>{{ $item->waktu_pembayaran?->format('d-m-Y H:i') ?? '-' }}</td>
          <td><a href="{{ route('admin.upah.edit', $item) }}" class="table-action">Kelola upah</a></td>
        </tr>
      @empty
        <tr><td colspan="6">Belum ada data upah pada hasil filter.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
