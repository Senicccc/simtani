@extends('layouts.backend')

@section('title', 'Tugas Saya')

@section('content')
<div class="page-head">
  <div>
    <h2>Tugas Saya</h2>
    <p>{{ $penugasan->count() }} tugas ditampilkan.</p>
  </div>
  <a href="{{ route('anggota.riwayat-tugas') }}" class="btn-outline">Riwayat Tugas</a>
</div>

<form method="get" class="filter-bar">
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kegiatan" aria-label="Cari kegiatan">
  <button type="submit" class="btn-primary">Cari</button>
  @if(request()->filled('q'))
    <a href="{{ route('anggota.tugas.index') }}" class="btn-outline">Bersihkan</a>
  @endif
</form>

<div class="member-task-list">
  @forelse($penugasan as $item)
    <article class="panel member-card">
      <div class="member-card__head">
        <span class="cell-name">{{ $item->jadwal->nama_kegiatan }}</span>
        @include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $item->status])
      </div>
      <dl class="detail-grid">
        <div><dt>Kategori</dt><dd>{{ $item->jadwal->jenisPekerjaan->nama_pekerjaan }}</dd></div>
        <div><dt>Tanggal</dt><dd>{{ $item->tanggalMulaiEfektif()->format('d/m/Y') }} – {{ $item->tanggalSelesaiEfektif()->format('d/m/Y') }}</dd></div>
        <div><dt>Lokasi</dt><dd>{{ $item->jadwal->lokasi }}</dd></div>
      </dl>
      <div class="task-card__action">
        <a href="{{ route('anggota.tugas.show', $item) }}" class="btn-primary btn-auto attendance-button">Lihat detail tugas dan presensi</a>
      </div>
    </article>
  @empty
    <div class="panel"><div class="empty-state">Belum ada tugas yang cocok.</div></div>
  @endforelse
</div>
@endsection