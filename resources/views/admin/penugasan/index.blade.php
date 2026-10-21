@extends('layouts.backend')

@section('title', 'Penugasan')

@section('content')
<div class="page-head">
  <div>
    <h2>Penugasan</h2>
    <p>{{ $jadwal->count() }} kegiatan ditampilkan.</p>
  </div>
  <a href="{{ route('admin.penugasan.create') }}" class="btn-outline">+ Tambah Anggota ke Tugas</a>
</div>

<form method="get" class="filter-bar">
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kegiatan atau anggota" aria-label="Cari kegiatan atau anggota">
  <select name="status" aria-label="Filter status tugas">
    <option value="">Semua status</option>
    @foreach (['active' => 'Semua tugas aktif', 'assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu verifikasi', 'completed' => 'Selesai', 'rejected' => 'Laporan ditolak', 'cancelled' => 'Dibatalkan'] as $value => $label)
      <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn-primary">Filter</button>
</form>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Kegiatan</th>
          <th>Tanggal</th>
          <th>Terisi</th>
          <th>Anggota dan Status</th>
          <th class="col-end">Kelola</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($jadwal as $item)
          @php $aktif = $item->penugasan->where('status', '!=', 'cancelled'); @endphp
          <tr>
            <td>
              <span class="cell-name">{{ $item->nama_kegiatan }}</span>
              <span class="cell-sub">{{ $item->jenisPekerjaan->nama_pekerjaan }}</span>
            </td>
            <td class="cell-num">
              {{ $item->tanggal?->format('d/m/Y') }}
              @if ($item->tanggal_selesai && ! $item->tanggal_selesai->isSameDay($item->tanggal))
                <span class="cell-sub">s/d {{ $item->tanggal_selesai->format('d/m/Y') }}</span>
              @endif
            </td>
            <td class="cell-num {{ $item->anggota_terisi < $item->jumlah_orang_dibutuhkan ? 'text-warn' : '' }}">
              {{ $item->anggota_terisi }} / {{ $item->jumlah_orang_dibutuhkan }}
            </td>
            <td>
              @if ($aktif->isEmpty())
                <span class="muted">Belum ada anggota</span>
              @else
                <ul class="member-list">
                  @foreach ($aktif as $assignment)
                    <li>
                      <a href="{{ route('admin.penugasan.show', $assignment) }}">{{ $assignment->anggota->nama_lengkap }}</a>
                      @include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $assignment->status])
                    </li>
                  @endforeach
                </ul>
              @endif
            </td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.jadwal.penugasan', $item) }}" class="table-action">Kelola Penugasan</a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5">
              <div class="empty-state">Tidak ada penugasan yang cocok dengan filter.</div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
