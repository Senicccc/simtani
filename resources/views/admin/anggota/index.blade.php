@extends('layouts.backend')

@section('title', 'Anggota')

@section('content')
<div class="page-head">
  <div>
    <h2>Anggota</h2>
    <p>{{ $anggota->count() }} anggota terdaftar.</p>
  </div>
  <a href="{{ route('admin.anggota.create') }}" class="btn-outline">+ Tambah Anggota</a>
</div>

<form method="get" class="filter-bar">
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau nomor anggota" aria-label="Cari anggota">
  <select name="status" aria-label="Filter status">
    <option value="">Semua status</option>
    <option value="1" @selected(request('status') === '1')>Aktif</option>
    <option value="0" @selected(request('status') === '0')>Nonaktif</option>
  </select>
  <button type="submit" class="btn-primary">Cari</button>
</form>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Nomor</th>
          <th>Anggota</th>
          <th>Status</th>
          <th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($anggota as $item)
          <tr>
            <td class="cell-num">{{ $item->nomor_anggota }}</td>
            <td>
              <span class="cell-name">{{ $item->nama_lengkap }}</span>
              <span class="cell-sub">{{ $item->email }}</span>
            </td>
            <td>
              @if ($item->status_aktif)
                <span class="badge">Aktif</span>
              @else
                <span class="badge-warn">Nonaktif</span>
              @endif
            </td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.anggota.edit', $item) }}" class="table-action">Edit</a>
                <form method="POST" action="{{ route('admin.anggota.destroy', $item) }}"
                      onsubmit="return confirm({{ \Illuminate\Support\Js::from('Hapus permanen '.$item->nama_lengkap.'? Anggota yang memiliki riwayat tugas tidak dapat dihapus.') }})">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="table-action danger">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4">
              <div class="empty-state">
                {{ request('q') || request('status') !== null && request('status') !== '' ? 'Tidak ada anggota yang cocok dengan pencarian.' : 'Belum ada anggota. Klik Tambah Anggota untuk mulai.' }}
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
