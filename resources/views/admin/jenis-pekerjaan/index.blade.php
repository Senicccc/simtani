@extends('layouts.backend')
@section('title', 'Jenis Pekerjaan')
@section('content')
<h2>Jenis Pekerjaan</h2><p><a href="{{ route('admin.jenis-pekerjaan.create') }}">Tambah jenis pekerjaan</a></p>
<table><thead><tr><th>Nama</th><th>Satuan</th><th>Aksi</th></tr></thead><tbody>
@forelse($jenisPekerjaan as $item)<tr><td>{{ $item->nama_pekerjaan }}</td><td>{{ $item->satuan }}</td><td><a href="{{ route('admin.jenis-pekerjaan.edit', $item) }}">Edit</a></td></tr>@empty<tr><td colspan="3">Belum ada kategori pekerjaan.</td></tr>@endforelse
</tbody></table>
@endsection
