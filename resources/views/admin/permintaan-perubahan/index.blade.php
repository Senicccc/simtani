@extends('layouts.backend')
@section('title', 'Permintaan Perubahan')
@section('content')<h2>Permintaan Perubahan</h2><table><thead><tr><th>Pengaju</th><th>Jenis</th><th>Alasan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($permintaan as $item)<tr><td>{{ $item->diajukanOleh->nama_lengkap ?? '-' }}</td><td>{{ $item->jenis_permintaan }}</td><td>{{ $item->alasan }}</td><td>{{ $item->status }}</td><td><a href="{{ route('admin.permintaan-perubahan.show', $item) }}">Detail</a></td></tr>@empty<tr><td colspan="5">Belum ada permintaan.</td></tr>@endforelse</tbody></table>@endsection
