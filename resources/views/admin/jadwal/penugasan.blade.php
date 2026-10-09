@extends('layouts.backend')

@section('title', 'Penugasan Jadwal')

@section('content')
<div class="view-header">
  <h2 style="margin:0">Penugasan: {{ $jadwal->nama_kegiatan }}</h2>
  <a href="{{ route('admin.jadwal.show', $jadwal) }}" class="btn-outline">Kembali ke jadwal</a>
</div>

<p>{{ $jadwal->tanggal?->format('d-m-Y') }} – {{ $jadwal->tanggal_selesai?->format('d-m-Y') }} | {{ $jadwal->jenisPekerjaan->nama_pekerjaan }} | {{ $jadwal->lokasi }}</p>
<p>Total upah kegiatan: {{ (float) $jadwal->total_upah > 0 ? 'Rp '.number_format((float) $jadwal->total_upah, 0, ',', '.') : 'Belum ditetapkan' }}. Dibagi rata setelah seluruh tugas selesai.</p>
<h3>Periksa kebutuhan anggota setiap hari</h3>
<p>Jika anggota izin atau tidak hadir, tambahkan pengganti hanya bila jumlah anggota yang hadir belum memenuhi kebutuhan.</p>
<div class="table-scroll">
  <table>
    <thead><tr><th>Tanggal</th><th>Anggota tersedia</th><th>Izin/tidak hadir</th><th>Tindakan admin</th></tr></thead>
    <tbody>
      @foreach($dailyCoverage as $day)
        <tr>
          <td>{{ $day['tanggal']->format('d-m-Y') }}</td>
          <td>{{ $day['anggota'] }} / {{ $day['kebutuhan'] }}</td>
          <td>{{ $day['izin'] }} anggota</td>
          <td>
            @if($day['izin'] > 0 && $day['anggota'] < $day['kebutuhan'])
              <form method="post" action="{{ route('admin.jadwal.pengganti-harian', $jadwal) }}" style="display:flex;gap:8px;flex-wrap:wrap">
                @csrf
                <input type="hidden" name="tanggal" value="{{ $day['tanggal']->toDateString() }}">
                <label for="pengganti-{{ $day['tanggal']->format('Ymd') }}">Pilih anggota pengganti untuk {{ $day['tanggal']->format('d-m-Y') }}</label>
                <select id="pengganti-{{ $day['tanggal']->format('Ymd') }}" name="anggota_id" required>
                  <option value="">Pilih nama anggota</option>
                  @foreach($activeMembers as $member)
                    <option value="{{ $member->id }}">{{ $member->nama_lengkap }}</option>
                  @endforeach
                </select>
                <button type="submit" class="btn-primary">Tugaskan pengganti hari ini</button>
              </form>
            @elseif($day['anggota'] < $day['kebutuhan'])
              <span>Masih diperlukan {{ $day['kebutuhan'] - $day['anggota'] }} anggota untuk memenuhi kebutuhan harian.</span>
            @else
              <span>Kuota harian terpenuhi</span>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
<div class="table-scroll">
  <table>
    <thead><tr><th>Anggota</th><th>Rentang penugasan</th><th>Status tugas</th><th>Presensi per hari</th><th>Upah</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse($jadwal->penugasan->where('status', '!=', 'cancelled') as $item)
        <tr>
          <td>{{ $item->anggota->nama_lengkap }}</td>
          <td>{{ $item->tanggalMulaiEfektif()->format('d-m-Y') }} – {{ $item->tanggalSelesaiEfektif()->format('d-m-Y') }}</td>
          <td>{{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td>
          <td>{{ $item->presensiHarian->map(fn ($presence) => $presence->tanggal->format('d-m').' '.ucfirst(str_replace('_', ' ', $presence->status)))->implode(', ') ?: 'Belum dicatat' }}</td>
          <td>
            @if($item->upah)
              Rp {{ number_format((float) $item->upah->jumlah_upah, 0, ',', '.') }} | {{ str_replace('_', ' ', $item->upah->status_pembayaran) }}
            @elseif($item->status === 'completed')
              Upah belum tersedia
            @else
              Menunggu pekerjaan selesai
            @endif
          </td>
          <td>
            <a href="{{ route('admin.penugasan.show', $item) }}" class="table-action">Detail</a>
            @if(!in_array($item->status, ['completed', 'cancelled'], true))
              <a href="{{ route('admin.penugasan.edit', $item) }}" class="table-action">Edit</a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6">Belum ada anggota yang ditugaskan.</td></tr>
      @endforelse

      @if($availableSlots > 0)
        <tr>
          <td colspan="6">
            <form method="post" action="{{ route('admin.penugasan.store') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
              @csrf
              <input type="hidden" name="jadwal_id" value="{{ $jadwal->id }}">
              <label for="anggota-search">Cari anggota untuk mengisi {{ $availableSlots }} posisi yang tersisa</label>
              <input id="anggota-search" type="search" placeholder="Ketik nama atau nomor anggota" autocomplete="off">
              <select id="anggota_id" name="anggota_id" required>
                <option value="">Pilih anggota aktif yang jadwalnya tersedia</option>
                @foreach($availableMembers as $member)
                  <option value="{{ $member['anggota_id'] }}" @selected(old('anggota_id') == $member['anggota_id'])>{{ $member['nama_lengkap'] }} ({{ $member['nomor_anggota'] }}), {{ $member['jumlah_tugas_aktif'] }} tugas aktif, {{ $member['jumlah_tugas_selesai'] }} tugas selesai, {{ $member['tingkat_kemampuan'] ? 'kompetensi '.$member['tingkat_kemampuan'] : 'kompetensi belum dicatat' }}</option>
                @endforeach
              </select>
              <button type="submit" class="btn-outline" @disabled(count($availableMembers) === 0)>Tambah anggota</button>
              @if(empty($availableMembers))
                <small>Tidak ada anggota aktif dengan jadwal yang tersedia pada waktu ini.</small>
              @endif
            </form>
          </td>
        </tr>
      @endif
    </tbody>
  </table>
</div>
@endsection

@push('scripts')
<script>
  const memberSearch = document.getElementById('anggota-search');
  const memberSelect = document.getElementById('anggota_id');
  memberSearch?.addEventListener('input', () => {
    const searchTerm = memberSearch.value.trim().toLocaleLowerCase();
    Array.from(memberSelect.options).forEach((option, index) => {
      option.hidden = index !== 0 && !option.text.toLocaleLowerCase().includes(searchTerm);
    });
    if (memberSelect.selectedOptions[0]?.hidden) {
      memberSelect.value = '';
    }
  });
</script>
@endpush
