@extends('layouts.backend')
@section('title', 'Jadwal')
@section('content')<h2>Jadwal</h2><p><a href="{{ route('admin.jadwal.create') }}">Tambah jadwal</a></p>
<table><thead><tr><th>Kegiatan</th><th>Tanggal</th><th>Pekerjaan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($jadwal as $item)<tr><td>{{ $item->nama_kegiatan }}</td><td>{{ $item->tanggal }}</td><td>{{ $item->jenisPekerjaan->nama_pekerjaan ?? '-' }}</td><td>{{ $item->status }}</td><td><a href="{{ route('admin.jadwal.show', $item) }}">Detail</a> <a href="{{ route('admin.jadwal.edit', $item) }}">Edit</a></td></tr>@empty<tr><td colspan="5">Belum ada jadwal.</td></tr>@endforelse
</tbody></table>@endsection
