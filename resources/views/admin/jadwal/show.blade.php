@extends('layouts.backend')
@section('title', 'Detail Jadwal')

@section('content')
<div class="page-head">
  <div>
    <h2>{{ $jadwal->nama_kegiatan }}</h2>
    <p>@include('admin.partials.status-badge', ['type' => 'jadwal', 'status' => $jadwal->status])</p>
  </div>
  <div class="page-actions">
    <a href="{{ route('admin.jadwal.edit', $jadwal) }}" class="btn-outline">Edit Jadwal</a>
    <a href="{{ route('admin.laporan.index', ['q' => $jadwal->nama_kegiatan]) }}" class="btn-outline">Lihat di Laporan</a>
    <a href="{{ route('admin.jadwal.penugasan', $jadwal) }}" class="btn-primary">Kelola Penugasan</a>
  </div>
</div>

@if ($jadwal->status === 'completed' && $jadwal->penugasan->contains(fn ($assignment) => $assignment->status === 'rejected') && today()->greaterThan($jadwal->tanggal_selesai ?? $jadwal->tanggal))
  <div class="banner mb-16">
    Jadwal ditutup karena tenggat berakhir setelah laporan ditolak.
    <a href="{{ route('admin.jadwal.edit', $jadwal) }}">Perpanjang atau ubah tanggal kegiatan</a> untuk membuka kembali jadwal.
  </div>
@endif

<div class="panel panel-body">
  @if ($jadwal->deskripsi)
    <p class="detail-desc">{{ $jadwal->deskripsi }}</p>
  @endif
  <dl class="detail-grid">
    <div><dt>Kategori pekerjaan</dt><dd>{{ $jadwal->jenisPekerjaan->nama_pekerjaan }}</dd></div>
    <div><dt>Tanggal kegiatan</dt><dd>{{ $jadwal->tanggal?->format('d/m/Y') }} – {{ $jadwal->tanggal_selesai?->format('d/m/Y') }}</dd></div>
    <div><dt>Waktu</dt><dd>{{ $jadwal->waktu_mulai }} - {{ $jadwal->waktu_selesai }}</dd></div>
    <div><dt>Lokasi</dt><dd>{{ $jadwal->lokasi }}</dd></div>
    <div><dt>Kebutuhan anggota</dt><dd>{{ $autoSchedule['assigned_count'] }} / {{ $autoSchedule['required_slots'] }}</dd></div>
    <div><dt>Hasil kerja</dt><dd>{{ $jadwal->jumlah_satuan }} {{ $jadwal->jenisPekerjaan->satuan }}</dd></div>
    <div>
      <dt>Total upah kegiatan</dt>
      <dd>
        {{ (float) $jadwal->total_upah > 0 ? 'Rp '.number_format((float) $jadwal->total_upah, 0, ',', '.') : 'Belum ditetapkan' }}
        <small>Dibagi rata setelah semua tugas selesai</small>
      </dd>
    </div>
    <div><dt>Penyelesaian tugas</dt><dd>{{ $jadwal->perlu_verifikasi ? 'Menunggu pemeriksaan admin' : 'Selesai otomatis saat anggota melapor' }}</dd></div>
  </dl>
</div>

<div class="panel panel-body">
  <h3>Penugasan Otomatis</h3>
  <p class="note">Rekomendasi mempertimbangkan tugas aktif, riwayat penyelesaian, kecocokan kompetensi, dan bentrok jadwal. Anggota tetap dapat dipilih meski kompetensinya belum dicatat.</p>
  <p><a href="{{ route('admin.jadwal.penugasan', $jadwal) }}#tambah-anggota" class="btn-outline">Tambah anggota secara manual</a></p>

  @if ($autoSchedule['vacancies'] === 0)
    <div class="banner-success">Kebutuhan anggota sudah terpenuhi. Anda tetap dapat melihat atau mengubah penugasan.</div>
  @elseif ($autoSchedule['can_generate'])
    @if ($autoSchedule['is_shortage'])
      <p class="note">{{ $autoSchedule['candidate_count'] }} anggota tersedia untuk {{ $autoSchedule['vacancies'] }} posisi. Posisi yang belum terisi dapat dilengkapi nanti.</p>
    @else
      <p class="note">Pilih {{ $autoSchedule['vacancies'] }} anggota dari rekomendasi berikut atau gunakan pilihan awal.</p>
    @endif
    <form method="post" action="{{ route('admin.jadwal.generate', $jadwal) }}" onsubmit="return confirm('Simpan anggota yang dipilih untuk tugas ini?')">
      @csrf
      <input type="hidden" name="selected_member_ids_present" value="1">
      <fieldset class="fieldset-plain">
        <legend>Pilih {{ $autoSchedule['recommended_count'] }} anggota</legend>
        @foreach ($autoSchedule['candidates'] as $candidate)
          <label class="candidate">
            <input type="checkbox" name="selected_member_ids[]" value="{{ $candidate['anggota_id'] }}"
                   @checked(in_array($candidate['anggota_id'], $autoSchedule['recommended_member_ids'], true))>
            <span>
              <span class="candidate-name">{{ $candidate['nama_lengkap'] }}</span>
              <span class="candidate-reason">{{ $candidate['reason'] }}</span>
            </span>
          </label>
        @endforeach
      </fieldset>
      <button type="submit" class="btn-primary btn-auto">Konfirmasi penugasan</button>
    </form>
  @else
    <div class="banner">{{ $autoSchedule['unavailable_reason'] }}</div>
  @endif
</div>

<h3 class="section-title">Anggota pada Kegiatan</h3>
<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Anggota</th>
          <th>Status Tugas</th>
          <th>Presensi</th>
          <th>Upah</th>
          <th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($jadwal->penugasan->where('status', '!=', 'cancelled') as $item)
          <tr>
            <td><span class="cell-name">{{ $item->anggota->nama_lengkap }}</span></td>
            <td>@include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $item->status])</td>
            <td class="cell-wrap">{{ $item->presensiHarian->map(fn ($presence) => $presence->tanggal->format('d/m').' '.($presence->status === 'hadir' ? 'Hadir' : ucfirst(str_replace('_', ' ', $presence->status))))->implode(', ') ?: 'Belum dicatat' }}</td>
            <td>{{ $item->upah ? 'Rp '.number_format((float) $item->upah->jumlah_upah, 0, ',', '.') : ($item->status === 'completed' ? 'Menunggu semua tugas selesai' : 'Menunggu pekerjaan selesai') }}</td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.penugasan.show', $item) }}" class="table-action">Detail Tugas</a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty-state">Belum ada anggota pada kegiatan ini.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection