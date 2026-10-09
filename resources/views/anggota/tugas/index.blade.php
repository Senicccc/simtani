@extends('layouts.backend')

@section('title', 'Tugas Saya')

@section('content')
<div class="view-header">
  <h2 style="margin:0">Tugas Saya</h2>
  <a href="{{ route('anggota.riwayat-tugas') }}" class="btn-outline">Riwayat tugas</a>
</div>

<form method="get" style="display:flex;gap:10px;margin:16px 0">
  <input name="q" value="{{ request('q') }}" placeholder="Cari kegiatan" aria-label="Cari kegiatan">
  <button type="submit" class="btn-outline">Cari</button>
  @if(request()->filled('q'))
    <a href="{{ route('anggota.tugas.index') }}" class="table-action">Bersihkan</a>
  @endif
</form>

<div class="member-task-list">
  @forelse($penugasan as $item)
    <article class="stat-card member-task-card">
      <h3>{{ $item->jadwal->nama_kegiatan }}</h3>
      <p><strong>Kategori:</strong> {{ $item->jadwal->jenisPekerjaan->nama_pekerjaan }}</p>
      <p><strong>Tanggal:</strong> {{ $item->tanggalMulaiEfektif()->format('d-m-Y') }} – {{ $item->tanggalSelesaiEfektif()->format('d-m-Y') }}</p>
      <p><strong>Lokasi:</strong> {{ $item->jadwal->lokasi }}</p>
      <p><strong>Status tugas:</strong> {{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</p>
      <a href="{{ route('anggota.tugas.show', $item) }}" class="btn-primary attendance-button">Lihat detail tugas dan presensi</a>
    </article>
  @empty
    <p class="placeholder-note">Belum ada tugas yang cocok.</p>
  @endforelse
</div>
@endsection
