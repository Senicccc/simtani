@extends('layouts.backend')
@section('title', 'Presensi')

@section('content')
<div class="page-head">
  <div>
    <h2>Presensi</h2>
    <p>{{ $presensi->count() }} catatan presensi ditampilkan.</p>
  </div>
  @if (Route::has('admin.presensi.create'))
    <a href="{{ route('admin.presensi.create') }}" class="btn-outline">+ Tambah Presensi</a>
  @endif
</div>

<form method="get" class="filter-bar">
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kegiatan atau anggota" aria-label="Cari kegiatan atau anggota">
  <button type="submit" class="btn-primary">Cari</button>
</form>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Anggota</th>
          <th>Kegiatan</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Status</th>
          <th>Upah</th>
          <th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($presensi as $item)
          <tr>
            <td><span class="cell-name">{{ $item->penugasan->anggota->nama_lengkap ?? '-' }}</span></td>
            <td>
              <span class="cell-name">{{ $item->penugasan->jadwal->nama_kegiatan ?? '-' }}</span>
              <span class="cell-sub">{{ $item->tanggal?->format('d/m/Y') }}</span>
            </td>
            <td class="cell-num">{{ $item->waktu_check_in?->format('d/m/Y H:i') ?? '-' }}</td>
            <td class="cell-num">{{ $item->waktu_check_out?->format('d/m/Y H:i') ?? '-' }}</td>
            <td>
              @include('admin.partials.status-badge', ['type' => 'presensi', 'status' => $item->status])
              @if ($item->status === 'izin')
                <span class="cell-sub">Tidak dihitung sebagai tugas selesai</span>
              @endif
            </td>
            <td>{{ $item->penugasan?->upah ? 'Rp '.number_format((float) $item->penugasan->upah->jumlah_upah, 0, ',', '.') : 'Belum dihitung' }}</td>
            <td>
<div class="row-actions">
  @if ($item->penugasan)
    <a href="{{ route('admin.penugasan.show', $item->penugasan) }}" class="table-action">Lihat Tugas</a>
  @endif
  @if (Route::has('admin.presensi.edit'))
    <a href="{{ route('admin.presensi.edit', $item) }}" class="table-action">Edit</a>
  @endif
</div>  
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7">
              <div class="empty-state">
                {{ request('q') ? 'Tidak ada presensi yang cocok dengan pencarian.' : 'Belum ada presensi.' }}
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
