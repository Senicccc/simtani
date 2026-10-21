@extends('layouts.backend')
@section('title', 'Riwayat Tugas')

@section('content')
<div class="page-head">
  <div>
    <h2>Riwayat Tugas</h2>
    <p>{{ $penugasan->count() }} tugas tercatat.</p>
  </div>
</div>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Kegiatan</th>
          <th>Tanggal Selesai</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($penugasan as $item)
          <tr>
            <td><span class="cell-name">{{ $item->jadwal->nama_kegiatan ?? '-' }}</span></td>
            <td class="cell-num">{{ $item->completed_at?->format('d/m/Y H:i') ?? '-' }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $item->status])</td>
          </tr>
        @empty
          <tr><td colspan="3"><div class="empty-state">Belum ada riwayat.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection