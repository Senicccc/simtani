@extends('layouts.backend')

@section('title', 'Anggota')

@section('content')
<div class="view-header">
  <h2 style="margin:0">Anggota</h2>
  <a href="{{ route('admin.anggota.create') }}" class="btn-outline" style="text-decoration:none">+ Tambah Anggota</a>
</div>

<form method="get" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau nomor anggota" style="flex:1;min-width:200px;padding:9px 12px;border:1px solid var(--line);border-radius:8px;font-size:13px">
  <select name="status" style="padding:9px 12px;border:1px solid var(--line);border-radius:8px;font-size:13px">
    <option value="">Semua status</option>
    <option value="1" @selected(request('status') === '1')>Aktif</option>
    <option value="0" @selected(request('status') === '0')>Nonaktif</option>
  </select>
  <button type="submit" class="btn-outline">Cari</button>
</form>

<div class="table-scroll">
  <table>
    <thead>
      <tr><th>Nomor</th><th>Nama</th><th>Email</th><th>Status</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      @forelse ($anggota as $item)
        <tr>
          <td>{{ $item->nomor_anggota }}</td>
          <td>{{ $item->nama_lengkap }}</td>
          <td>{{ $item->email }}</td>
          <td>
            @if ($item->status_aktif)
              <span class="badge">Aktif</span>
            @else
              <span class="badge-warn">Nonaktif</span>
            @endif
          </td>
          <td>
            <a href="{{ route('admin.anggota.edit', $item) }}" class="table-action">Edit</a>
            <form method="POST" action="{{ route('admin.anggota.destroy', $item) }}" style="display:inline" onsubmit="return confirm('Hapus permanen {{ $item->nama_lengkap }}? Cuma bisa kalau belum pernah dapat penugasan.')">
              @csrf
              @method('DELETE')
              <button type="submit" class="table-action danger" style="background:none;border:none;padding:0;font:inherit;cursor:pointer">Hapus</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="color:var(--text-muted)">Belum ada anggota.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
