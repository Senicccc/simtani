@extends('layouts.backend')
@section('title', 'Penugasan')
@section('content')<h2>Penugasan</h2><table><thead><tr><th>Kegiatan</th><th>Anggota</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($penugasan as $item)<tr><td>{{ $item->jadwal->nama_kegiatan ?? '-' }}</td><td>{{ $item->anggota->nama_lengkap ?? '-' }}</td><td>{{ $item->status }}</td><td><a href="{{ route('admin.penugasan.show', $item) }}">Detail</a></td></tr>@empty<tr><td colspan="4">Belum ada penugasan.</td></tr>@endforelse</tbody></table>@endsection
<p><a href="{{ route('admin.penugasan.create') }}">Tambah penugasan manual</a></p>
