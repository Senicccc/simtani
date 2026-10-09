@extends('layouts.backend')

@section('title', 'Detail Tugas')

@section('content')
<div class="view-header">
  <h2 style="margin:0">{{ $penugasan->jadwal->nama_kegiatan }}</h2>
  <a href="{{ route('anggota.tugas.index') }}" class="btn-outline">Kembali ke daftar tugas</a>
</div>

<div class="stat-card attendance-today">
  <h3>Sebelum mulai</h3>
  <p>Periksa tanggal, waktu, lokasi, dan jumlah hasil kerja sebelum mencatat presensi.</p>
</div>

<div class="stat-card">
  <h3>Detail kegiatan</h3>
  <dl>
    <dt>Jenis pekerjaan</dt><dd>{{ $penugasan->jadwal->jenisPekerjaan->nama_pekerjaan }}</dd>
    <dt>Tanggal kegiatan</dt><dd>{{ $penugasan->tanggalMulaiEfektif()->format('d-m-Y') }} – {{ $penugasan->tanggalSelesaiEfektif()->format('d-m-Y') }}</dd>
    <dt>Waktu</dt><dd>{{ $penugasan->jadwal->waktu_mulai }} - {{ $penugasan->jadwal->waktu_selesai }}</dd>
    <dt>Lokasi</dt><dd>{{ $penugasan->jadwal->lokasi }}</dd>
    <dt>Status tugas</dt><dd>{{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan admin', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$penugasan->status] ?? $penugasan->status }}</dd>
    <dt>Jumlah hasil kerja</dt><dd>{{ $penugasan->jadwal->jumlah_satuan }} {{ $penugasan->jadwal->jenisPekerjaan->satuan }}</dd>
  </dl>
</div>

@include('anggota.tugas._daily-attendance-actions', ['penugasan' => $penugasan])

<div class="stat-row">
  <div class="stat-card">
    <h3>Riwayat presensi harian</h3>
    @foreach(\Carbon\CarbonPeriod::create($penugasan->tanggalMulaiEfektif(), $penugasan->tanggalSelesaiEfektif()) as $date)
      @php($dailyPresence = $penugasan->presensiHarian->first(fn ($presence) => $presence->tanggal->isSameDay($date)))
      <div class="attendance-day">
        <strong>{{ $date->format('d-m-Y') }}{{ $date->isToday() ? ' · Hari ini' : '' }}</strong>
        @if($dailyPresence)
          <p>{{ ['hadir' => 'Hadir', 'izin' => 'Izin', 'tidak_hadir' => 'Tidak hadir'][$dailyPresence->status] ?? $dailyPresence->status }}</p>
          @if($dailyPresence->status === 'hadir')
            <p>Check-in {{ $dailyPresence->waktu_check_in?->format('H:i') ?? '-' }} · check-out {{ $dailyPresence->waktu_check_out?->format('H:i') ?? 'Belum check-out' }}</p>
          @else
            <p>Admin akan memilih pengganti jika jumlah anggota yang hadir kurang dari kebutuhan.</p>
          @endif
        @elseif(in_array($penugasan->status, ['assigned', 'in_progress'], true) && $date->greaterThan(today()))
          <p>Belum dicatat.</p>
          <form method="post" action="{{ route('anggota.presensi.status', $penugasan) }}" class="attendance-leave-form">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $date->toDateString() }}">
            <input type="hidden" name="status" value="izin">
            <label for="leave-note-{{ $date->format('Ymd') }}">Alasan izin (wajib)</label>
            <textarea id="leave-note-{{ $date->format('Ymd') }}" name="catatan" maxlength="1000" required>{{ old('catatan') }}</textarea>
            <button type="submit" class="btn-outline attendance-button">Izin tanggal ini · minta pengganti</button>
          </form>
          <form method="post" action="{{ route('anggota.presensi.status', $penugasan) }}" class="attendance-actions">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $date->toDateString() }}">
            <input type="hidden" name="status" value="tidak_hadir">
            <button type="submit" class="btn-outline attendance-button">Tidak hadir tanggal ini · minta pengganti</button>
          </form>
        @else
          <p>Belum ada catatan presensi.</p>
        @endif
      </div>
    @endforeach
    <a href="{{ route('anggota.presensi.index') }}" class="btn-outline attendance-history-link">Lihat semua riwayat presensi</a>
  </div>
  <div class="stat-card">
    <h3>Upah</h3>
    @if($penugasan->upah)
      <p>Rp {{ number_format((float) $penugasan->upah->jumlah_upah, 0, ',', '.') }}</p>
      <p>Status: {{ $penugasan->upah->status_pembayaran === 'sudah_dibayar' ? 'Sudah dibayar' : 'Belum dibayar' }}</p>
      <p>Pembayaran: {{ $penugasan->upah->waktu_pembayaran?->format('d-m-Y H:i') ?? 'Belum dibayar' }}</p>
    @else
      @if((float) $penugasan->jadwal->total_upah > 0)
        <p>Total upah kegiatan: Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }}. Dibagi rata setelah semua anggota menyelesaikan tugas.</p>
      @else
        <p>Upah belum ditetapkan. Hubungi admin sebelum mulai atau menyelesaikan tugas.</p>
      @endif
      @if($penugasan->status === 'waiting_verification')
        <p>Laporan menunggu pemeriksaan admin. Bagian upah tersedia setelah semua tugas anggota disetujui.</p>
      @elseif($penugasan->jadwal->perlu_verifikasi)
        <p>Setelah semua anggota menyelesaikan tugas dan admin menyetujui laporan, total upah dibagi rata ke daftar pembayaran.</p>
      @else
        <p>Tugas selesai otomatis saat laporan dikirim. Bagian upah dibagi rata setelah semua anggota menyelesaikan tugas.</p>
      @endif
    @endif
    <a href="{{ route('anggota.upah.index') }}" class="btn-outline attendance-history-link">Lihat upah saya</a>
  </div>
</div>

@if($rekanTugas->isNotEmpty())
  <h3>Anggota lain pada tugas ini</h3>
  <ul>@foreach($rekanTugas as $rekan)<li>{{ $rekan->anggota->nama_lengkap }}: {{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$rekan->status] ?? $rekan->status }}</li>@endforeach</ul>
@endif

@if($penugasan->status === 'in_progress')
  <p><a href="{{ route('anggota.tugas.complete.form', $penugasan) }}" class="btn-primary attendance-button">Laporkan pekerjaan selesai</a></p>
@endif
@if(!in_array($penugasan->status, ['completed', 'cancelled'], true))
  <p><a href="{{ route('anggota.permintaan-perubahan.create', ['penugasan_id' => $penugasan->id]) }}" class="btn-outline">Minta perubahan tugas</a></p>
@endif
<p><a href="{{ route('anggota.hubungi-admin') }}" class="btn-outline">Hubungi admin jika perlu bantuan</a></p>
@endsection
