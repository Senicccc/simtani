@extends('layouts.backend')

@section('title', 'Penugasan')

@section('content')
<div class="view-header">
  <h2 style="margin:0">Penugasan</h2>
  <a href="{{ route('admin.penugasan.create') }}" class="btn-outline">Tambah anggota ke tugas</a>
</div>
<form method="get" style="display:flex;gap:10px;margin:16px 0;flex-wrap:wrap">
  <input name="q" value="{{ request('q') }}" placeholder="Cari kegiatan atau anggota">
  <select name="status" aria-label="Filter status tugas">
    <option value="">Semua status</option>
    @foreach(['active' => 'Semua tugas aktif', 'assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu verifikasi', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
      <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn-outline">Filter</button>
</form>
<div class="table-scroll">
  <table>
    <thead><tr><th>Kegiatan</th><th>Kategori</th><th>Tanggal</th><th>Anggota</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse($jadwal as $item)
        <tr>
          <td>{{ $item->nama_kegiatan }}</td>
          <td>{{ $item->jenisPekerjaan->nama_pekerjaan }}</td>
          <td>{{ $item->tanggal?->format('d-m-Y') }}</td>
          <td>
            {{ $item->anggota_terisi }} / {{ $item->jumlah_orang_dibutuhkan }}
            @if($item->tanggal_selesai && ! $item->tanggal_selesai->isSameDay($item->tanggal))
              <br><small>{{ $item->tanggal->format('d-m-Y') }} – {{ $item->tanggal_selesai->format('d-m-Y') }}</small>
            @endif
          </td>
          <td>
            @foreach($item->penugasan->where('status', '!=', 'cancelled') as $assignment)
              <div>{{ $assignment->anggota->nama_lengkap }}: {{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$assignment->status] ?? $assignment->status }} <a href="{{ route('admin.penugasan.show', $assignment) }}">Buka detail</a></div>
            @endforeach
          </td>
          <td>
            <a href="{{ route('admin.jadwal.show', $item) }}" class="table-action">Detail dan edit tugas</a>
            <a href="{{ route('admin.jadwal.penugasan', $item) }}" class="table-action">Kelola anggota</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="6">Tidak ada penugasan yang cocok dengan filter.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
