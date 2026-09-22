@extends('layouts.backend')
@section('title', 'Anggota')
@section('content')
<h2>Anggota</h2>
<p><a href="{{ route('admin.anggota.create') }}">Tambah anggota</a></p><form method="get"><input name="q" value="{{ request('q') }}" placeholder="Cari nama atau nomor anggota"><select name="status"><option value="">Semua status</option><option value="1" @selected(request('status') === '1')>Aktif</option><option value="0" @selected(request('status') === '0')>Nonaktif</option></select><button type="submit">Cari</button></form>
<table><thead><tr><th>Nomor</th><th>Nama</th><th>Email</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($anggota as $item)<tr><td>{{ $item->nomor_anggota }}</td><td>{{ $item->nama_lengkap }}</td><td>{{ $item->email }}</td><td>{{ $item->status_aktif ? 'Aktif' : 'Tidak aktif' }}</td><td><a href="{{ route('admin.anggota.edit', $item) }}">Edit</a></td></tr>@empty<tr><td colspan="5">Belum ada anggota.</td></tr>@endforelse
</tbody></table>
@endsection
