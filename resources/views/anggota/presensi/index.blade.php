@extends('layouts.backend')
@section('title', 'Presensi Saya')

@section('content')
<div class="page-head">
  <div>
    <h2>Riwayat Kehadiran</h2>
    <p>Kehadiran dan izin dicatat terpisah untuk setiap tanggal kegiatan.</p>
  </div>
</div>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Kegiatan</th>
          <th>Mulai</th>
          <th>Selesai</th>
          <th>Status</th>
          <th class="col-end">Tugas</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($presensi as $item)
          <tr>
            <td>
              <span class="cell-name">{{ $item->penugasan->jadwal->nama_kegiatan ?? '-' }}</span>
              <span class="cell-sub">{{ $item->tanggal?->format('d-m-Y') }}</span>
            </td>
            <td class="cell-num">{{ $item->waktu_check_in?->format('d-m-Y H:i') ?? '-' }}</td>
            <td class="cell-num">{{ $item->waktu_check_out?->format('d-m-Y H:i') ?? '-' }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'presensi', 'status' => $item->status])</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('anggota.tugas.show', $item->penugasan) }}" class="table-action">Buka Tugas</a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty-state">Belum ada riwayat kehadiran.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection