@extends('layouts.backend')
@section('title', 'Tugas Saya')
@section('content')<h2>Tugas Saya</h2><table><thead><tr><th>Kegiatan</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($penugasan as $item)<tr><td>{{ $item->jadwal->nama_kegiatan ?? '-' }}</td><td>{{ $item->jadwal->tanggal ?? '-' }}</td><td>{{ $item->status }}</td><td><a href="{{ route('anggota.tugas.show', $item) }}">Detail</a></td></tr>@empty<tr><td colspan="4">Belum ada tugas.</td></tr>@endforelse</tbody></table>@endsection
