@extends('layouts.backend')
@section('title', 'Penugasan Jadwal')
@section('content')<h2>Penugasan: {{ $jadwal->nama_kegiatan }}</h2><table><thead><tr><th>Anggota</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($jadwal->penugasan as $item)<tr><td>{{ $item->anggota->nama_lengkap ?? '-' }}</td><td>{{ $item->status }}</td><td><a href="{{ route('admin.penugasan.show', $item) }}">Detail</a></td></tr>@empty<tr><td colspan="3">Belum ada penugasan.</td></tr>@endforelse</tbody></table>@endsection
