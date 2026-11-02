@extends('layouts.backend')

@section('title', 'Beranda Anggota')

@section('content')
<h2>Beranda Anggota</h2>
<section class="stat-card member-quick-start" aria-labelledby="quick-start-title">
  <h3 id="quick-start-title">Presensi hari ini</h3>
  <p>Presensi dicatat dari halaman detail tugas pada hari kegiatan.</p>
  <a href="{{ route('anggota.tugas.index') }}" class="btn-primary btn-auto member-quick-start__button">Lihat tugas saya</a>
</section>
<div class="member-task-list">
  @forelse($memberTasks as $item)
    <article class="stat-card member-task-card">
      <h3>{{ $item->jadwal->nama_kegiatan }}</h3>
      <p><strong>Tanggal:</strong> {{ $item->tanggalMulaiEfektif()->format('d/m/Y') }} – {{ $item->tanggalSelesaiEfektif()->format('d/m/Y') }}</p>
      <p><strong>Lokasi:</strong> {{ $item->jadwal->lokasi }}</p>
      <a href="{{ route('anggota.tugas.show', $item) }}" class="btn-primary attendance-button">Buka tugas dan presensi</a>
    </article>
  @empty
    <p class="placeholder-note">Belum ada tugas aktif atau mendatang.</p>
  @endforelse
</div>
<h3 class="section-title member-section-title">Ringkasan aktivitas</h3>
<div class="stat-row member-stat-grid" aria-label="Ringkasan aktivitas">
    <a href="{{ route('anggota.tugas.index') }}" class="stat-card">
      <span class="k">Tugas aktif</span>
      <span class="v">{{ $activeTasks }} tugas</span>
    </a>
    <a href="{{ route('anggota.tugas.index') }}" class="stat-card">
      <span class="k">Tugas mendatang</span>
      <span class="v">{{ $upcomingTasks }} kegiatan</span>
    </a>
    <a href="{{ route('anggota.riwayat-tugas') }}" class="stat-card">
      <span class="k">Tugas selesai</span>
      <span class="v">{{ $completedTasks }} tugas</span>
    </a>
    <a href="{{ route('anggota.upah.index') }}" class="stat-card">
      <span class="k">Upah belum dibayar</span>
      <span class="v">{{ $unpaidWages }} pembayaran</span>
    </a>
</div>
<h3 class="section-title member-section-title">Layanan lainnya</h3>
<div class="member-shortcuts">
  <a href="{{ route('anggota.permintaan-perubahan.index') }}" class="stat-card">
      <span class="k">Permintaan perubahan</span>
      <span class="v">{{ $pendingRequests }} menunggu diproses</span>
  </a>
  <a href="{{ route('anggota.presensi.index') }}" class="stat-card">
      <span class="k">Riwayat presensi</span>
      <span class="v">Lihat catatan hadir dan izin</span>
  </a>
</div>
@endsection
