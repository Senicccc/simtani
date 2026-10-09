@extends('layouts.backend')

@section('title', 'Permintaan Perubahan Saya')

@section('content')
<div class="view-header">
  <h2 style="margin:0">Permintaan Perubahan Tugas</h2>
  <a href="{{ route('anggota.permintaan-perubahan.create') }}" class="btn-outline">Buat permintaan</a>
</div>
<div class="table-scroll">
  <table>
    <thead><tr><th>Tugas saya</th><th>Jenis</th><th>Tugas tujuan</th><th>Alasan</th><th>Status</th><th>Catatan admin</th></tr></thead>
    <tbody>
      @forelse($permintaan as $item)
        <tr>
          <td><a href="{{ route('anggota.tugas.show', $item->penugasan) }}">{{ $item->penugasan->jadwal->nama_kegiatan }}</a></td>
          <td>{{ $item->jenis_permintaan === 'tukar' ? 'Pindah jadwal' : 'Sanggah tugas' }}</td>
          <td>
            @if($item->targetJadwal)
              {{ $item->targetJadwal->nama_kegiatan }} · {{ $item->targetJadwal->tanggal?->format('d-m-Y') }}
            @elseif($item->targetPenugasan)
              <a href="{{ route('anggota.tugas.show', $item->targetPenugasan) }}">{{ $item->targetPenugasan->jadwal->nama_kegiatan }} · {{ $item->targetPenugasan->anggota->nama_lengkap }}</a>
            @else
              -
            @endif
          </td>
          <td>{{ $item->alasan }}</td>
          <td>{{ ['pending' => 'Menunggu diproses', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$item->status] ?? $item->status }}</td>
          <td>{{ $item->catatan_admin ?: '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="6">Belum ada permintaan perubahan.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
