@extends('layouts.backend')
@section('title', 'Upah Saya')
@section('content')<h2>Upah Saya</h2><table><thead><tr><th>Kegiatan</th><th>Jumlah</th><th>Status</th></tr></thead><tbody>@forelse($upah as $item)<tr><td>{{ $item->penugasan->jadwal->nama_kegiatan ?? '-' }}</td><td>{{ $item->jumlah_upah }}</td><td>{{ $item->status_pembayaran }}</td></tr>@empty<tr><td colspan="3">Belum ada data upah.</td></tr>@endforelse</tbody></table>@endsection
