@extends('layouts.backend')
@section('title', 'Jadwal')
@section('content')<h2>Jadwal</h2>
<form method="get" style="display:flex;gap:10px;margin:16px 0;flex-wrap:wrap">
  <label for="schedule-status">Filter jadwal</label>
  <select id="schedule-status" name="status">
    <option value="">Semua status</option>
    @foreach(['draft' => 'Draf', 'scheduled' => 'Terjadwal', 'ongoing' => 'Sedang berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
      <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
    @endforeach
  </select>
  <label><input type="checkbox" name="needs_replacement" value="1" @checked(request()->boolean('needs_replacement'))> Perlu pengganti</label>
  <button type="submit" class="btn-outline">Terapkan filter</button>
  <a href="{{ route('admin.jadwal.index') }}" class="btn-outline">Hapus filter</a>
</form>
<p><a href="{{ route('admin.jadwal.create') }}">Tambah jadwal</a></p>
<table><thead><tr><th>Kegiatan</th><th>Tanggal</th><th>Pekerjaan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($jadwal as $item)<tr><td>{{ $item->nama_kegiatan }}</td><td>{{ $item->tanggal?->format('d-m-Y') }}@if($item->tanggal_selesai && ! $item->tanggal_selesai->isSameDay($item->tanggal)) – {{ $item->tanggal_selesai->format('d-m-Y') }}@endif</td><td>{{ $item->jenisPekerjaan->nama_pekerjaan ?? '-' }}</td><td>{{ ['draft' => 'Draf', 'scheduled' => 'Terjadwal', 'ongoing' => 'Sedang berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td><td><a href="{{ route('admin.jadwal.show', $item) }}">Lihat detail</a> <a href="{{ route('admin.jadwal.edit', $item) }}">Ubah</a></td></tr>@empty<tr><td colspan="5">Belum ada jadwal.</td></tr>@endforelse
</tbody></table>@endsection
