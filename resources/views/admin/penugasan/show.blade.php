@extends('layouts.backend')
@section('title', 'Detail Penugasan')

@section('content')
@php $jadwal = $penugasan->jadwal; @endphp

<div class="page-head">
  <div>
    <h2>{{ $jadwal->nama_kegiatan }}</h2>
    <p>@include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $penugasan->status])</p>
  </div>
  <div class="page-actions">
    <a href="{{ route('admin.jadwal.show', $jadwal) }}" class="btn-outline">Kembali ke Detail Jadwal</a>
    @if ($penugasan->status === 'completed')
      <a href="{{ route('admin.upah.index', ['jadwal_id' => $penugasan->jadwal_id]) }}" class="btn-primary">Lihat upah kegiatan ini</a>
    @endif
  </div>
</div>

<div class="panel panel-body">
  <dl class="detail-grid">
    <div><dt>Jenis pekerjaan</dt><dd>{{ $jadwal->jenisPekerjaan->nama_pekerjaan }}</dd></div>
    <div><dt>Tanggal kegiatan</dt><dd>{{ $penugasan->tanggalMulaiEfektif()->format('d/m/Y') }} – {{ $penugasan->tanggalSelesaiEfektif()->format('d/m/Y') }}</dd></div>
    <div><dt>Waktu</dt><dd>{{ $jadwal->waktu_mulai }} - {{ $jadwal->waktu_selesai }}</dd></div>
    <div><dt>Lokasi</dt><dd>{{ $jadwal->lokasi }}</dd></div>
    <div>
      <dt>Total upah kegiatan</dt>
      <dd>
        Rp {{ number_format((float) $jadwal->total_upah, 0, ',', '.') }}
        <small>Bagian per anggota dihitung rata setelah semua tugas selesai.</small>
      </dd>
    </div>
    <div><dt>Penyelesaian</dt><dd>{{ $jadwal->perlu_verifikasi ? 'Admin memeriksa laporan sebelum menyetujui upah.' : 'Tugas selesai otomatis saat anggota mengirim laporan.' }}</dd></div>
  </dl>
</div>

<div class="section-head">
  <h3>Daftar Anggota dan Laporan</h3>
  <a href="{{ route('admin.jadwal.penugasan', $jadwal) }}" class="btn-outline">Kelola atau Tambah Anggota</a>
</div>

@forelse ($jadwal->penugasan->where('status', '!=', 'cancelled') as $item)
  @php $bisaDiubah = in_array($item->status, ['assigned', 'in_progress'], true); @endphp
  <div class="panel member-card {{ $item->is($penugasan) ? 'is-current' : '' }}">
    <div class="member-card__head">
      <div>
        <span class="cell-name">{{ $item->anggota->nama_lengkap }}</span>
        @if ($item->is($penugasan))
          <span class="cell-sub">Tugas yang dibuka</span>
        @endif
      </div>
      @include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $item->status])
    </div>

    <dl class="detail-grid">
      <div>
        <dt>Kehadiran</dt>
        <dd>{{ $item->presensiHarian->map(fn ($presence) => $presence->tanggal->format('d/m').' '.ucfirst(str_replace('_', ' ', $presence->status)))->implode(', ') ?: 'Belum dicatat' }}</dd>
      </div>
      <div>
        <dt>Upah</dt>
        <dd>
          @if ($item->upah)
            Rp {{ number_format((float) $item->upah->jumlah_upah, 0, ',', '.') }}
            <small>@include('admin.partials.status-badge', ['type' => 'bayar', 'status' => $item->upah->status_pembayaran])</small>
          @else
            <span class="muted">Menunggu semua tugas anggota selesai agar total upah dapat dibagi rata.</span>
          @endif
        </dd>
      </div>
      <div>
        <dt>Laporan selesai</dt>
        <dd>
          @if ($item->catatan_penyelesaian)
            {{ $item->catatan_penyelesaian }}
          @else
            <span class="muted">Belum ada laporan.</span>
          @endif
          @if ($item->bukti_selesai)
            <a class="detail-link" href="{{ asset('storage/'.$item->bukti_selesai) }}" target="_blank" rel="noopener">Lihat bukti</a>
          @endif
        </dd>
      </div>
      @if ($item->catatan_verifikasi)
        <div class="span-all">
          <dt>{{ $item->status === 'rejected' ? 'Alasan penolakan laporan' : ($item->verified_by ? 'Catatan pemeriksaan admin' : 'Keterangan penyelesaian') }}</dt>
          <dd>{{ $item->catatan_verifikasi }}</dd>
        </div>
      @endif
    </dl>

    @if ($bisaDiubah || $item->status === 'waiting_verification')
      <div class="member-card__actions">
        @if ($bisaDiubah)
          <a href="{{ route('admin.penugasan.edit', $item) }}" class="btn-outline">Edit Penugasan</a>
        @endif

        @if ($item->status === 'waiting_verification')
          <form method="post" action="{{ route('admin.penugasan.verify', $item) }}"
                onsubmit="return confirm('Setujui laporan? Upah dibagi setelah semua anggota selesai.')">
            @csrf
            <button name="action" value="approve" type="submit" class="btn-primary">Setujui Laporan</button>
          </form>
          <form method="post" action="{{ route('admin.penugasan.verify', $item) }}" class="verification-rejection">
            @csrf
            <label class="inline-label" for="reject-note-{{ $item->id }}">Alasan penolakan laporan</label>
            <textarea id="reject-note-{{ $item->id }}" name="catatan_verifikasi" required maxlength="2000" placeholder="Jelaskan alasan laporan ditolak"></textarea>
            <button name="action" value="reject" type="submit" class="btn-danger" onclick="return confirm('Tolak laporan dan tandai anggota tidak hadir tanpa upah?')">Tolak Laporan · Tidak Hadir</button>
          </form>
        @endif
      </div>
    @endif
  </div>
@empty
  <div class="panel"><div class="empty-state">Belum ada anggota pada kegiatan ini.</div></div>
@endforelse
@endsection