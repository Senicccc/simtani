@extends('layouts.backend')
@section('title', 'Jadwal')

@section('content')
<div class="page-head">
  <div>
    <h2>Jadwal</h2>
    <p>{{ $jadwal->count() }} jadwal ditampilkan.</p>
  </div>
  <a href="{{ route('admin.jadwal.create') }}" class="btn-outline">+ Tambah Jadwal</a>
</div>

<form method="get" class="filter-bar">
  <label for="schedule-status" class="sr-only">Filter jadwal</label>
  <select id="schedule-status" name="status">
    <option value="">Semua status</option>
    @foreach(['draft' => 'Draf', 'scheduled' => 'Terjadwal', 'ongoing' => 'Sedang berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
      <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
    @endforeach
  </select>
  <label class="check-row">
    <input type="checkbox" name="needs_replacement" value="1" @checked(request()->boolean('needs_replacement'))>
    <span>Perlu pengganti</span>
  </label>
  <button type="submit" class="btn-primary">Terapkan Filter</button>
  <a href="{{ route('admin.jadwal.index') }}" class="btn-outline">Hapus Filter</a>
</form>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Kegiatan</th>
          <th>Tanggal</th>
          <th>Status</th>
          <th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($jadwal as $item)
          <tr>
            <td>
              <span class="cell-name">{{ $item->nama_kegiatan }}</span>
              <span class="cell-sub">{{ $item->jenisPekerjaan->nama_pekerjaan ?? '-' }}</span>
            </td>
            <td class="cell-num">
              {{ $item->tanggal?->format('d-m-Y') }}@if($item->tanggal_selesai && ! $item->tanggal_selesai->isSameDay($item->tanggal)) – {{ $item->tanggal_selesai->format('d-m-Y') }}@endif
            </td>
            <td>@include('admin.partials.status-badge', ['type' => 'jadwal', 'status' => $item->status])</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.jadwal.show', $item) }}" class="table-action">Lihat Detail</a>
                <a href="{{ route('admin.jadwal.edit', $item) }}" class="table-action">Ubah</a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4">
              <div class="empty-state">
                {{ request('status') || request()->boolean('needs_replacement') ? 'Tidak ada jadwal yang cocok dengan filter.' : 'Belum ada jadwal. Klik Tambah Jadwal untuk mulai.' }}
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection