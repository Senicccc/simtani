@extends('layouts.backend')
@section('title', 'Riwayat Tugas')
@section('content')<h2>Riwayat Tugas</h2><table><thead><tr><th>Kegiatan</th><th>Tanggal selesai</th><th>Status</th></tr></thead><tbody>@forelse($penugasan as $item)<tr><td>{{ $item->jadwal->nama_kegiatan ?? '-' }}</td><td>{{ $item->completed_at }}</td><td>{{ $item->status }}</td></tr>@empty<tr><td colspan="3">Belum ada riwayat.</td></tr>@endforelse</tbody></table>@endsection
