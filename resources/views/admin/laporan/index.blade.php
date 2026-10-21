@extends('layouts.backend')

@section('title', 'Laporan')

@section('content')
<div class="page-head">
  <div>
    <h2>Laporan Operasional</h2>
    <p>Periode {{ $start ?: 'awal' }} s/d {{ $end ?: 'akhir' }}</p>
  </div>
  <div class="page-actions">
    <a href="{{ route('admin.laporan.export.csv', request()->query()) }}" class="btn-outline">Unduh CSV</a>
    <a href="{{ route('admin.laporan.export', request()->query()) }}" class="btn-outline">Cetak / PDF</a>
  </div>
</div>

<form method="get" class="panel panel-body">
  <div class="filter-grid">
    <div class="field">
      <label for="tanggal_mulai">Mulai</label>
      <input id="tanggal_mulai" type="date" name="tanggal_mulai" value="{{ $start }}">
    </div>
    <div class="field">
      <label for="tanggal_selesai">Selesai</label>
      <input id="tanggal_selesai" type="date" name="tanggal_selesai" value="{{ $end }}">
    </div>
    <div class="field">
      <label for="kategori">Kategori pekerjaan</label>
      <select id="kategori" name="kategori">
        <option value="">Semua kategori</option>
        @foreach ($kategoriList as $item)
          <option value="{{ $item->id }}" @selected((string) $kategori === (string) $item->id)>{{ $item->nama_pekerjaan }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label for="anggota">Anggota</label>
      <select id="anggota" name="anggota">
        <option value="">Semua anggota</option>
        @foreach ($anggotaList as $item)
          <option value="{{ $item->id }}" @selected((string) $anggota === (string) $item->id)>{{ $item->nama_lengkap }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label for="q">Nama kegiatan / anggota</label>
      <input id="q" name="q" value="{{ $q }}" placeholder="Cari nama">
    </div>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn-primary">Terapkan Filter</button>
    <a href="{{ route('admin.laporan.index') }}" class="btn-outline">Bersihkan</a>
  </div>
</form>

<div class="stat-row">
  <div class="stat-card"><div class="k">Jadwal ditemukan</div><div class="v">{{ $rekapJadwal->count() }}</div></div>
  <div class="stat-card"><div class="k">Penugasan selesai</div><div class="v">{{ $rekapTugas->where('status', 'completed')->count() }}</div></div>
  <div class="stat-card"><div class="k">Presensi hadir</div><div class="v">{{ $rekapPresensi->where('status', 'hadir')->count() }} / {{ $rekapPresensi->count() }}</div></div>
  <div class="stat-card"><div class="k">Upah belum dibayar</div><div class="v">Rp {{ number_format((float) $rekapUpah->where('status_pembayaran', 'belum_dibayar')->sum('jumlah_upah'), 0, ',', '.') }}</div></div>
</div>

<h3 class="section-title">Jadwal Kegiatan</h3>
<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Tanggal</th><th>Kegiatan</th><th>Jenis Pekerjaan</th><th>Lokasi</th><th>Status</th><th class="col-end">Penugasan</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rekapJadwal as $item)
          <tr>
            <td class="cell-num">{{ $item->tanggal?->format('d/m/Y') }}</td>
            <td><span class="cell-name">{{ $item->nama_kegiatan }}</span></td>
            <td>{{ $item->jenisPekerjaan->nama_pekerjaan ?? '-' }}</td>
            <td>{{ $item->lokasi }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'jadwal', 'status' => $item->status])</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.jadwal.penugasan', $item) }}" class="table-action">Lihat Anggota</a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state">Tidak ada jadwal yang cocok dengan filter.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<h3 class="section-title">Penugasan dan Hasil Kerja</h3>
<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Kegiatan</th><th>Anggota</th><th>Status</th><th>Presensi</th><th>Upah</th><th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rekapTugas as $item)
          <tr>
            <td>
              <span class="cell-name">{{ $item->jadwal->nama_kegiatan }}</span>
              <span class="cell-sub">{{ $item->jadwal->tanggal?->format('d/m/Y') }}</span>
            </td>
            <td>{{ $item->anggota->nama_lengkap }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $item->status])</td>
            <td>@include('admin.partials.status-badge', ['type' => 'presensi', 'status' => $item->presensi?->status ?? 'belum'])</td>
            <td>{{ $item->upah ? 'Rp '.number_format((float) $item->upah->jumlah_upah, 0, ',', '.') : 'Belum dihitung' }}</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.penugasan.show', $item) }}" class="table-action">Detail Tugas</a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state">Tidak ada penugasan yang cocok dengan filter.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<h3 class="section-title">Rincian Presensi</h3>
<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Kegiatan</th><th>Anggota</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Catatan</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rekapPresensi as $item)
          <tr>
            <td>{{ $item->penugasan->jadwal->nama_kegiatan }}</td>
            <td>{{ $item->penugasan->anggota->nama_lengkap }}</td>
            <td class="cell-num">{{ $item->waktu_check_in?->format('d/m/Y H:i') ?? '-' }}</td>
            <td class="cell-num">{{ $item->waktu_check_out?->format('d/m/Y H:i') ?? '-' }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'presensi', 'status' => $item->status])</td>
            <td class="cell-wrap">{{ $item->catatan ?: '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state">Belum ada presensi pada hasil filter.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<h3 class="section-title">Rincian Upah</h3>
<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Kegiatan</th><th>Anggota</th><th>Upah Disepakati</th><th>Status Pembayaran</th><th>Pembayaran</th><th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rekapUpah as $item)
          <tr>
            <td>{{ $item->penugasan->jadwal->nama_kegiatan }}</td>
            <td>{{ $item->penugasan->anggota->nama_lengkap }}</td>
            <td class="cell-num">Rp {{ number_format((float) $item->jumlah_upah, 0, ',', '.') }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'bayar', 'status' => $item->status_pembayaran])</td>
            <td class="cell-num">{{ $item->waktu_pembayaran?->format('d/m/Y H:i') ?? '-' }}</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.upah.edit', $item) }}" class="table-action">Kelola Upah</a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state">Belum ada data upah pada hasil filter.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection