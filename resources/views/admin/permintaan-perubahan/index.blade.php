@extends('layouts.backend')
@section('title', 'Permintaan Perubahan Tugas')
@section('content')<h2>Permintaan Perubahan Tugas</h2>
<form method="get" style="display:flex;gap:10px;margin:16px 0;flex-wrap:wrap">
  <label for="request-status">Filter status</label>
  <select id="request-status" name="status">
    <option value="">Semua status</option>
    <option value="pending" @selected(request('status') === 'pending')>Menunggu diproses</option>
    <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
    <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
  </select>
  <button type="submit" class="btn-outline">Terapkan filter</button>
  <a href="{{ route('admin.permintaan-perubahan.index') }}" class="btn-outline">Hapus filter</a>
</form>
<table><thead><tr><th>Pengaju</th><th>Jenis</th><th>Alasan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($permintaan as $item)<tr><td>{{ $item->diajukanOleh->nama_lengkap ?? '-' }}</td><td>{{ ['tukar' => 'Pindah jadwal', 'sanggah' => 'Sanggah tugas'][$item->jenis_permintaan] ?? $item->jenis_permintaan }}</td><td>{{ $item->alasan }}</td><td>{{ ['pending' => 'Menunggu diproses', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$item->status] ?? $item->status }}</td><td><a href="{{ route('admin.permintaan-perubahan.show', $item) }}">Lihat detail</a></td></tr>@empty<tr><td colspan="5">Tidak ada permintaan yang sesuai dengan filter.</td></tr>@endforelse</tbody></table>@endsection
