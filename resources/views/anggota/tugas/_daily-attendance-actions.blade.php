@php
  $today = today();
  $todayPresence = $penugasan->presensiHarian->first(fn ($presence) => $presence->tanggal->isSameDay($today));
  $canManageAttendance = in_array($penugasan->status, ['assigned', 'in_progress'], true);
  $isScheduledToday = $today->betweenIncluded($penugasan->tanggalMulaiEfektif(), $penugasan->tanggalSelesaiEfektif());
@endphp

<section class="daily-attendance" aria-label="Presensi hari ini untuk {{ $penugasan->jadwal->nama_kegiatan }}">
  <h4>Presensi hari ini · {{ $today->format('d-m-Y') }}</h4>
  @if($penugasan->status === 'waiting_verification')
    <p>Laporan tugas sedang diperiksa admin.</p>
  @elseif(!$canManageAttendance)
    <p>Tugas ini sudah {{ $penugasan->status === 'completed' ? 'selesai' : 'tidak aktif' }}.</p>
  @elseif($today->lt($penugasan->tanggalMulaiEfektif()))
    <p>Presensi mulai tersedia pada {{ $penugasan->tanggalMulaiEfektif()->format('d-m-Y') }}. Datang kembali pada tanggal tersebut untuk check-in.</p>
  @elseif($today->gt($penugasan->tanggalSelesaiEfektif()))
    <p>Masa presensi berakhir pada {{ $penugasan->tanggalSelesaiEfektif()->format('d-m-Y') }}. Hubungi admin jika ada presensi yang belum dicatat.</p>
  @elseif(!$isScheduledToday)
    <p>Presensi belum tersedia hari ini.</p>
  @elseif($todayPresence?->status === 'izin' || $todayPresence?->status === 'tidak_hadir')
    <p class="attendance-status-warning">Status hari ini: {{ $todayPresence->status === 'izin' ? 'izin' : 'tidak hadir' }}. Admin akan menugaskan pengganti jika jumlah anggota yang hadir belum mencukupi.</p>
  @elseif($todayPresence?->waktu_check_out)
    <p class="attendance-status">Presensi selesai. Check-in {{ $todayPresence->waktu_check_in?->format('H:i') ?? '-' }} · check-out {{ $todayPresence->waktu_check_out->format('H:i') }}.</p>
  @elseif($todayPresence?->status === 'hadir' && $todayPresence->waktu_check_in)
    <p class="attendance-status">Anda sudah check-in pukul {{ $todayPresence->waktu_check_in->format('H:i') }}.</p>
    <form method="post" action="{{ route('anggota.tugas.check-out', $penugasan) }}" onsubmit="return confirm('Apakah Anda yakin ingin check-out dari tugas ini sekarang?')">
      @csrf
      <button type="submit" class="btn-primary attendance-button">Catat check-out hari ini · Check-out sekarang</button>
    </form>
  @else
    <p>Pilih tindakan yang sesuai dengan kehadiran Anda hari ini.</p>
    <div class="attendance-actions">
      <form method="post" action="{{ route('anggota.tugas.mulai', $penugasan) }}" onsubmit="return confirm('Apakah Anda yakin ingin check-in dan mulai tugas ini sekarang?')">
        @csrf
        <button type="submit" class="btn-primary attendance-button">Mulai dan catat hadir hari ini · Check-in</button>
      </form>
      <form method="post" action="{{ route('anggota.presensi.status', $penugasan) }}" class="attendance-leave-form">
        @csrf
        <input type="hidden" name="tanggal" value="{{ $today->toDateString() }}">
        <input type="hidden" name="status" value="izin">
        <label for="leave-note-{{ $penugasan->id }}">Izin hari ini · tulis alasan</label>
        <textarea id="leave-note-{{ $penugasan->id }}" name="catatan" maxlength="1000" required placeholder="Contoh: sakit atau ada keperluan keluarga">{{ old('catatan') }}</textarea>
        <button type="submit" class="btn-outline attendance-button">Kirim izin · minta pengganti</button>
      </form>
      <form method="post" action="{{ route('anggota.presensi.status', $penugasan) }}">
        @csrf
        <input type="hidden" name="tanggal" value="{{ $today->toDateString() }}">
        <input type="hidden" name="status" value="tidak_hadir">
        <button type="submit" class="btn-outline attendance-button">Saya tidak hadir · minta pengganti</button>
      </form>
    </div>
  @endif
</section>
