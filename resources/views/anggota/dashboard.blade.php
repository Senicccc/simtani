@extends('layouts.backend')

@section('title', 'Beranda Anggota')

@section('content')
<h2>Beranda Anggota</h2>
<section class="stat-card member-quick-start" aria-labelledby="quick-start-title">
  <h3 id="quick-start-title">Presensi hari ini</h3>
  <p>Buka detail tugas untuk mencatat kehadiran, mengajukan izin, atau melakukan check-out pada tanggal kegiatan.</p>
  <a href="{{ route('anggota.tugas.index') }}" class="btn-outline attendance-history-link">Lihat semua tugas</a>
</section>
<div class="member-task-list">
  @forelse($memberTasks as $item)
    <article class="stat-card member-task-card">
      <h3>{{ $item->jadwal->nama_kegiatan }}</h3>
      <p><strong>Tanggal:</strong> {{ $item->tanggalMulaiEfektif()->format('d/m/Y') }} – {{ $item->tanggalSelesaiEfektif()->format('d/m/Y') }}</p>
      <p><strong>Lokasi:</strong> {{ $item->jadwal->lokasi }}</p>
      <a href="{{ route('anggota.tugas.show', $item) }}" class="btn-primary attendance-button">Lihat detail tugas dan presensi</a>
    </article>
  @empty
    <p class="placeholder-note">Belum ada tugas aktif atau mendatang.</p>
  @endforelse
</div>
<div class="stat-row">
  <a href="{{ route('anggota.tugas.index') }}" class="stat-card">
    <div class="k">Tugas aktif</div>
    <div class="v">{{ $activeTasks }} tugas · presensi tersedia di detail tugas</div>
  </a>
  <a href="{{ route('anggota.tugas.index') }}" class="stat-card">
    <div class="k">Tugas mendatang</div>
    <div class="v">{{ $upcomingTasks }}</div>
  </a>
</div>
<div class="stat-row">
  <a href="{{ route('anggota.riwayat-tugas') }}" class="stat-card">
    <div class="k">Tugas selesai</div>
    <div class="v">{{ $completedTasks }}</div>
  </a>
  <a href="{{ route('anggota.upah.index') }}" class="stat-card">
    <div class="k">Upah belum dibayar</div>
    <div class="v">{{ $unpaidWages }}</div>
  </a>
</div>
<div class="stat-row">
  <a href="{{ route('anggota.permintaan-perubahan.index') }}" class="stat-card">
    <div class="k">Permintaan perubahan menunggu diproses</div>
    <div class="v">{{ $pendingRequests }}</div>
  </a>
  <a href="{{ route('anggota.presensi.index') }}" class="stat-card">
    <div class="k">Riwayat presensi</div>
    <div class="v">Lihat catatan kehadiran</div>
  </a>
</div>
@endsection
