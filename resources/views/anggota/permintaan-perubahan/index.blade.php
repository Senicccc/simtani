@extends('layouts.backend')
@section('title', 'Permintaan Perubahan Saya')
@section('content')<h2>Permintaan Perubahan Saya</h2><p><a href="{{ route('anggota.permintaan-perubahan.create') }}">Buat permintaan</a></p><table><thead><tr><th>Jenis</th><th>Alasan</th><th>Status</th></tr></thead><tbody>@forelse($permintaan as $item)<tr><td>{{ $item->jenis_permintaan }}</td><td>{{ $item->alasan }}</td><td>{{ $item->status }}</td></tr>@empty<tr><td colspan="3">Belum ada permintaan.</td></tr>@endforelse</tbody></table>@endsection
