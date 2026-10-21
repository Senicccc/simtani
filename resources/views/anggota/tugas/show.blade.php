@extends('layouts.backend')

@section('title', 'Detail Tugas')

@section('content')
<div class="page-head">
  <div>
    <h2>{{ $penugasan->jadwal->nama_kegiatan }}</h2>
    <p>@include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $penugasan->status])</p>
  </div>
  <a href="{{ route('anggota.tugas.index') }}" class="btn-outline">Kembali ke daftar tugas</a>
</div>

<div class="panel panel-body">
  <h3>Detail kegiatan</h3>
  <p class="note">Periksa tanggal, waktu, lokasi, dan jumlah hasil kerja sebelum mencatat presensi.</p>
  <dl class="detail-grid">
    <div><dt>Jenis pekerjaan</dt><dd>{{ $penugasan->jadwal->jenisPekerjaan->nama_pekerjaan }}</dd></div>
    <div><dt>Tanggal kegiatan</dt><dd>{{ $penugasan->tanggalMulaiEfektif()->format('d/m/Y') }} – {{ $penugasan->tanggalSelesaiEfektif()->format('d/m/Y') }}</dd></div>
    <div><dt>Waktu</dt><dd>{{ $penugasan->jadwal->waktu_mulai }} - {{ $penugasan->jadwal->waktu_selesai }}</dd></div>
    <div><dt>Lokasi</dt><dd>{{ $penugasan->jadwal->lokasi }}</dd></div>
    <div><dt>Jumlah hasil kerja</dt><dd>{{ $penugasan->jadwal->jumlah_satuan }} {{ $penugasan->jadwal->jenisPekerjaan->satuan }}</dd></div>
  </dl>
</div>

@include('anggota.tugas._daily-attendance-actions', ['penugasan' => $penugasan])

<div class="panel panel-body">
  <h3>Riwayat Presensi Harian</h3>
  @foreach(\Carbon\CarbonPeriod::create($penugasan->tanggalMulaiEfektif(), $penugasan->tanggalSelesaiEfektif()) as $date)
    @php($dailyPresence = $penugasan->presensiHarian->first(fn ($presence) => $presence->tanggal->isSameDay($date)))
    <div class="attendance-day">
      <div class="attendance-day-head">
        <strong>{{ $date->format('d/m/Y') }}</strong>
        @if($date->isToday())<span class="badge-muted">Hari ini</span>@endif
        @if($dailyPresence)@include('admin.partials.status-badge', ['type' => 'presensi', 'status' => $dailyPresence->status])@endif
      </div>
      @if($dailyPresence)
        @if($dailyPresence->status === 'hadir')
          <p class="note">Check-in {{ $dailyPresence->waktu_check_in?->format('H:i') ?? '-' }} · check-out {{ $dailyPresence->waktu_check_out?->format('H:i') ?? 'Belum check-out' }}</p>
        @else
          <p class="note">Admin akan memilih pengganti jika jumlah anggota yang hadir kurang dari kebutuhan.</p>
        @endif
      @elseif(in_array($penugasan->status, ['assigned', 'in_progress'], true) && $date->greaterThan(today()))
        <p class="note">Belum dicatat.</p>
        <form method="post" action="{{ route('anggota.presensi.status', $penugasan) }}" class="attendance-leave-form">
          @csrf
          <input type="hidden" name="tanggal" value="{{ $date->toDateString() }}">
          <input type="hidden" name="status" value="izin">
          <label for="leave-note-{{ $date->format('Ymd') }}">Alasan izin (wajib)</label>
          <textarea id="leave-note-{{ $date->format('Ymd') }}" name="catatan" maxlength="1000" required>{{ old('catatan') }}</textarea>
          <button type="submit" class="btn-outline attendance-button">Izin tanggal ini · minta pengganti</button>
        </form>
        <form method="post" action="{{ route('anggota.presensi.status', $penugasan) }}">
          @csrf
          <input type="hidden" name="tanggal" value="{{ $date->toDateString() }}">
          <input type="hidden" name="status" value="tidak_hadir">
          <button type="submit" class="btn-outline attendance-button">Tidak hadir tanggal ini · minta pengganti</button>
        </form>
      @else
        <p class="note">Belum ada catatan presensi.</p>
      @endif
    </div>
  @endforeach
  <a href="{{ route('anggota.presensi.index') }}" class="btn-outline attendance-history-link">Lihat semua riwayat presensi</a>
</div>

<div class="panel panel-body">
  <h3>Upah</h3>
  @if($penugasan->upah)
    <p class="amount">Rp {{ number_format((float) $penugasan->upah->jumlah_upah, 0, ',', '.') }}</p>
    <p class="note">
      @include('admin.partials.status-badge', ['type' => 'bayar', 'status' => $penugasan->upah->status_pembayaran])
      Pembayaran: {{ $penugasan->upah->waktu_pembayaran?->format('d/m/Y H:i') ?? 'Belum dibayar' }}
    </p>
  @else
    @if((float) $penugasan->jadwal->total_upah > 0)
      <p class="note">Total upah kegiatan: Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }}. Dibagi rata setelah semua anggota menyelesaikan tugas.</p>
    @else
      <p class="note">Upah belum ditetapkan. Hubungi admin sebelum mulai atau menyelesaikan tugas.</p>
    @endif
    @if($penugasan->status === 'waiting_verification')
      <p class="note">Laporan menunggu pemeriksaan admin. Bagian upah tersedia setelah semua tugas anggota disetujui.</p>
    @elseif($penugasan->jadwal->perlu_verifikasi)
      <p class="note">Setelah semua anggota menyelesaikan tugas dan admin menyetujui laporan, total upah dibagi rata ke daftar pembayaran.</p>
    @else
      <p class="note">Tugas selesai otomatis saat laporan dikirim. Bagian upah dibagi rata setelah semua anggota menyelesaikan tugas.</p>
    @endif
  @endif
  <a href="{{ route('anggota.upah.index') }}" class="btn-outline attendance-history-link">Lihat upah saya</a>
</div>

@if($rekanTugas->isNotEmpty())
  <h3 class="section-title">Anggota Lain pada Tugas Ini</h3>
  <div class="panel panel-body">
    <ul class="member-list">
      @foreach($rekanTugas as $rekan)
        <li>
          <span class="cell-name">{{ $rekan->anggota->nama_lengkap }}</span>
          @include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $rekan->status])
        </li>
      @endforeach
    </ul>
  </div>
@endif

<div class="page-actions page-actions--bottom">
  @if($penugasan->status === 'assigned')
    <a href="{{ route('anggota.permintaan-perubahan.create', ['penugasan_id' => $penugasan->id]) }}" class="btn-outline">Minta Perubahan Tugas</a>
  @endif
  <a href="{{ route('anggota.hubungi-admin') }}" class="btn-outline">Hubungi Admin jika Perlu Bantuan</a>
</div>
@endsection