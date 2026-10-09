@extends('layouts.backend')

@section('title', 'Beranda Admin')

@section('content')
<h2>Beranda Admin</h2>
<div class="stat-row">
  <a href="{{ route('admin.anggota.index', ['status' => 1]) }}" class="stat-card">
    <div class="k">Anggota aktif</div>
    <div class="v">{{ $activeMembers }} orang</div>
  </a>
  <a href="{{ route('admin.jadwal.index') }}" class="stat-card">
    <div class="k">Total jadwal</div>
    <div class="v">{{ $totalSchedules }}</div>
  </a>
  <a href="{{ route('admin.jadwal.index', ['status' => 'scheduled']) }}" class="stat-card">
    <div class="k">Terjadwal</div>
    <div class="v">{{ $scheduledSchedules }}</div>
  </a>
  <a href="{{ route('admin.jadwal.index', ['status' => 'ongoing']) }}" class="stat-card">
    <div class="k">Sedang berlangsung</div>
    <div class="v">{{ $ongoingSchedules }}</div>
  </a>
</div>
<div class="stat-row">
  <a href="{{ route('admin.jadwal.index', ['status' => 'completed']) }}" class="stat-card">
    <div class="k">Jadwal selesai</div>
    <div class="v">{{ $completedSchedules }}</div>
  </a>
  <a href="{{ route('admin.jadwal.index', ['needs_replacement' => 1]) }}" class="stat-card">
    <div class="k">Posisi pengganti yang perlu diisi</div>
    <div class="v">{{ $leaveActions }}</div>
  </a>
  <a href="{{ route('admin.penugasan.index', ['status' => 'active']) }}" class="stat-card">
    <div class="k">Penugasan aktif</div>
    <div class="v">{{ $tasksRunning }}</div>
  </a>
</div>
<div class="stat-row">
  <a href="{{ route('admin.penugasan.index', ['status' => 'waiting_verification']) }}" class="stat-card">
    <div class="k">Laporan menunggu pemeriksaan</div>
    <div class="v">{{ $tasksToVerify }}</div>
  </a>
  <a href="{{ route('admin.permintaan-perubahan.index', ['status' => 'pending']) }}" class="stat-card">
    <div class="k">Permintaan perubahan menunggu diproses</div>
    <div class="v">{{ $pendingRequests }}</div>
  </a>
  <a href="{{ route('admin.upah.index', ['status_pembayaran' => 'belum_dibayar']) }}" class="stat-card">
    <div class="k">Upah belum dibayar</div>
    <div class="v">{{ $unpaidWages }}</div>
  </a>
</div>
<section class="stat-card admin-unpaid-wages">
  <div class="view-header">
    <h3 style="margin:0">Daftar upah belum dibayar</h3>
    <a href="{{ route('admin.upah.index', ['status_pembayaran' => 'belum_dibayar']) }}" class="btn-outline">Lihat semua upah belum dibayar ({{ $unpaidWages }})</a>
  </div>
  @if($unpaidWageItems->isNotEmpty())
    <div class="table-scroll">
      <table>
        <thead><tr><th>Anggota</th><th>Kegiatan</th><th>Jumlah</th><th>Aksi</th></tr></thead>
        <tbody>
          @foreach($unpaidWageItems as $item)
            <tr>
              <td>{{ $item->penugasan->anggota->nama_lengkap ?? '-' }}</td>
              <td>{{ $item->penugasan->jadwal->nama_kegiatan ?? '-' }}</td>
              <td>Rp {{ number_format((float) $item->jumlah_upah, 0, ',', '.') }}</td>
              <td><a href="{{ route('admin.upah.index', ['status_pembayaran' => 'belum_dibayar', 'jadwal_id' => $item->penugasan->jadwal_id]) }}" class="table-action">Lihat upah kegiatan</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <p>Tidak ada upah yang menunggu pembayaran.</p>
  @endif
</section>
<p><a href="{{ route('admin.laporan.index') }}">Lihat ringkasan jadwal, presensi, penugasan, dan pembayaran di laporan operasional.</a></p>
@endsection
