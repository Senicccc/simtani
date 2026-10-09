@extends('layouts.backend')

@section('title', 'Detail Jadwal')

@section('content')
<div class="view-header">
  <h2 style="margin:0">{{ $jadwal->nama_kegiatan }}</h2>
  <a href="{{ route('admin.jadwal.penugasan', $jadwal) }}" class="btn-outline">Kelola penugasan</a>
</div>

<p>{{ $jadwal->deskripsi }}</p>
<dl>
  <dt>Kategori pekerjaan</dt><dd>{{ $jadwal->jenisPekerjaan->nama_pekerjaan }}</dd>
  <dt>Tanggal kegiatan</dt><dd>{{ $jadwal->tanggal?->format('d-m-Y') }} – {{ $jadwal->tanggal_selesai?->format('d-m-Y') }}</dd>
  <dt>Waktu</dt><dd>{{ $jadwal->waktu_mulai }} - {{ $jadwal->waktu_selesai }}</dd>
  <dt>Lokasi</dt><dd>{{ $jadwal->lokasi }}</dd>
  <dt>Status</dt><dd>{{ ['draft' => 'Draf', 'scheduled' => 'Terjadwal', 'ongoing' => 'Sedang berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$jadwal->status] ?? $jadwal->status }}</dd>
  <dt>Kebutuhan anggota</dt><dd>{{ $autoSchedule['assigned_count'] }} / {{ $autoSchedule['required_slots'] }}</dd>
  <dt>Hasil kerja</dt><dd>{{ $jadwal->jumlah_satuan }} {{ $jadwal->jenisPekerjaan->satuan }}</dd>
  <dt>Total upah kegiatan</dt><dd>{{ (float) $jadwal->total_upah > 0 ? 'Rp '.number_format((float) $jadwal->total_upah, 0, ',', '.') : 'Belum ditetapkan' }} <small>(dibagi rata setelah semua tugas selesai)</small></dd>
  <dt>Penyelesaian tugas</dt><dd>{{ $jadwal->perlu_verifikasi ? 'Menunggu pemeriksaan admin' : 'Selesai otomatis saat anggota melapor' }}</dd>
</dl>

<p>
  <a href="{{ route('admin.jadwal.edit', $jadwal) }}" class="table-action">Edit jadwal</a>
  <a href="{{ route('admin.laporan.index', ['q' => $jadwal->nama_kegiatan]) }}" class="table-action">Lihat di laporan</a>
</p>

<section class="stat-card" style="margin:20px 0">
  <h3>Penugasan otomatis</h3>
  <p>Rekomendasi mempertimbangkan tugas aktif, riwayat penyelesaian, kecocokan kompetensi, dan bentrok jadwal. Anggota tetap dapat dipilih meski kompetensinya belum dicatat.</p>
  @if($autoSchedule['vacancies'] === 0)
    <p>Kebutuhan anggota sudah terpenuhi. Anda tetap dapat melihat atau mengubah penugasan.</p>
  @elseif($autoSchedule['can_generate'])
    @if($autoSchedule['is_shortage'])
      <p>{{ $autoSchedule['candidate_count'] }} anggota tersedia untuk {{ $autoSchedule['vacancies'] }} posisi. Posisi yang belum terisi dapat dilengkapi nanti.</p>
    @else
      <p>Pilih {{ $autoSchedule['vacancies'] }} anggota dari rekomendasi berikut atau gunakan pilihan awal.</p>
    @endif
    <form method="post" action="{{ route('admin.jadwal.generate', $jadwal) }}" onsubmit="return confirm('Simpan anggota yang dipilih untuk tugas ini?')">
      @csrf
      <input type="hidden" name="selected_member_ids_present" value="1">
      <fieldset>
        <legend>Pilih {{ $autoSchedule['recommended_count'] }} anggota</legend>
        @foreach($autoSchedule['candidates'] as $candidate)
          <label style="display:block;margin:8px 0">
            <input type="checkbox" name="selected_member_ids[]" value="{{ $candidate['anggota_id'] }}" @checked(in_array($candidate['anggota_id'], $autoSchedule['recommended_member_ids'], true))>
            {{ $candidate['nama_lengkap'] }}. {{ $candidate['reason'] }}.
          </label>
        @endforeach
      </fieldset>
      <button type="submit" class="btn-outline">Konfirmasi penugasan</button>
    </form>
  @else
    <p>{{ $autoSchedule['unavailable_reason'] }}</p>
  @endif
</section>

<h3>Anggota pada kegiatan</h3>
<div class="table-scroll">
  <table>
    <thead><tr><th>Anggota</th><th>Status tugas</th><th>Presensi</th><th>Upah</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse($jadwal->penugasan->where('status', '!=', 'cancelled') as $item)
        <tr>
          <td>{{ $item->anggota->nama_lengkap }}</td>
          <td>{{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td>
          <td>{{ $item->presensiHarian->map(fn ($presence) => $presence->tanggal->format('d-m').' '.($presence->status === 'hadir' ? 'Hadir' : ucfirst(str_replace('_', ' ', $presence->status))))->implode(', ') ?: 'Belum dicatat' }}</td>
          <td>{{ $item->upah ? 'Rp '.number_format((float) $item->upah->jumlah_upah, 0, ',', '.') : ($item->status === 'completed' ? 'Menunggu semua tugas selesai' : 'Menunggu pekerjaan selesai') }}</td>
          <td><a href="{{ route('admin.penugasan.show', $item) }}" class="table-action">Detail tugas</a></td>
        </tr>
      @empty
        <tr><td colspan="5">Belum ada anggota pada kegiatan ini.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
