@extends('layouts.backend')

@section('title', 'Permintaan Perubahan Saya')

@section('content')
<div class="page-head">
  <div>
    <h2>Permintaan Perubahan Tugas</h2>
    <p>{{ $permintaan->count() }} permintaan tercatat.</p>
  </div>
  <a href="{{ route('anggota.permintaan-perubahan.create') }}" class="btn-outline">Buat Permintaan</a>
</div>

<div class="panel">
  <div class="table-scroll">
    <table class="member-mobile-table member-mobile-table--stacked">
      <thead>
        <tr>
          <th>Tugas Saya</th>
          <th>Jenis</th>
          <th>Tugas Tujuan</th>
          <th>Alasan</th>
          <th>Status</th>
          <th>Catatan Admin</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($permintaan as $item)
          <tr>
            <td class="member-mobile-primary" data-label="Tugas saya"><a href="{{ route('anggota.tugas.show', $item->penugasan) }}" class="table-action">{{ $item->penugasan->jadwal->nama_kegiatan }}</a></td>
            <td data-label="Jenis permintaan">{{ $item->jenis_permintaan === 'tukar' ? 'Pindah jadwal' : 'Sanggah tugas' }}</td>
            <td data-label="Tugas tujuan">
              @if ($item->targetJadwal)
                {{ $item->targetJadwal->nama_kegiatan }} · {{ $item->targetJadwal->tanggal?->format('d/m/Y') }}
              @elseif ($item->targetPenugasan)
                <a href="{{ route('anggota.tugas.show', $item->targetPenugasan) }}" class="table-action">{{ $item->targetPenugasan->jadwal->nama_kegiatan }} · {{ $item->targetPenugasan->anggota->nama_lengkap }}</a>
              @else
                -
              @endif
            </td>
            <td class="cell-reason" data-label="Alasan">{{ $item->alasan }}</td>
            <td data-label="Status">@include('admin.partials.status-badge', ['type' => 'permintaan', 'status' => $item->status])</td>
            <td class="cell-reason" data-label="Catatan admin">{{ $item->catatan_admin ?: '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state">Belum ada permintaan perubahan.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection