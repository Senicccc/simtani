@extends('layouts.backend')

@section('title', 'Penugasan Jadwal')

@section('content')
<div class="page-head">
  <div>
    <h2>Penugasan: {{ $jadwal->nama_kegiatan }}</h2>
    <p class="meta-row">
      <span>{{ $jadwal->tanggal?->format('d/m/Y') }} – {{ $jadwal->tanggal_selesai?->format('d/m/Y') }}</span>
      <span>{{ $jadwal->jenisPekerjaan->nama_pekerjaan }}</span>
      <span>{{ $jadwal->lokasi }}</span>
    </p>
    <p>Total upah kegiatan: {{ (float) $jadwal->total_upah > 0 ? 'Rp '.number_format((float) $jadwal->total_upah, 0, ',', '.') : 'Belum ditetapkan' }}. Dibagi rata setelah seluruh tugas selesai.</p>
  </div>
  <a href="{{ route('admin.jadwal.show', $jadwal) }}" class="btn-outline">Kembali ke jadwal</a>
</div>

<h3 class="section-title">Periksa Kebutuhan Anggota Setiap Hari</h3>
<p class="section-note">Jika anggota izin atau tidak hadir, tugaskan pengganti atau lanjutkan dengan anggota yang hadir selama masih ada minimal satu orang.</p>
<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr><th>Tanggal</th><th>Anggota Tersedia</th><th>Izin/Tidak Hadir</th><th>Tindakan Admin</th></tr>
      </thead>
      <tbody>
        @foreach ($dailyCoverage as $day)
          <tr>
            <td class="cell-num">{{ $day['tanggal']->format('d/m/Y') }}</td>
            <td class="cell-num {{ $day['anggota'] < $day['kebutuhan'] ? 'text-warn' : '' }}">{{ $day['anggota'] }} / {{ $day['kebutuhan'] }}</td>
            <td>{{ $day['izin'] }} anggota</td>
            <td>
              @if ($day['izin'] > 0 && $day['anggota'] < $day['kebutuhan'])
                @if ($day['diterima'])
                  <p class="badge">Dilanjutkan dengan {{ $day['anggota'] }} anggota</p>
                @elseif ($day['anggota'] > 0)
                  <form method="post" action="{{ route('admin.jadwal.terima-kekurangan', $jadwal) }}" class="inline-form">
                    @csrf
                    <input type="hidden" name="tanggal" value="{{ $day['tanggal']->toDateString() }}">
                    <button type="submit" class="btn-outline" onclick="return confirm('Lanjutkan kegiatan dengan {{ $day['anggota'] }} anggota yang tersedia?')">Lanjutkan dengan anggota yang ada</button>
                  </form>
                @else
                  <span class="text-warn">Minimal satu anggota harus tersedia untuk melanjutkan.</span>
                @endif
                <form method="post" action="{{ route('admin.jadwal.pengganti-harian', $jadwal) }}" class="inline-form">
                  @csrf
                  <input type="hidden" name="tanggal" value="{{ $day['tanggal']->toDateString() }}">
                  <label class="sr-only" for="pengganti-{{ $day['tanggal']->format('Ymd') }}">Pilih anggota pengganti untuk {{ $day['tanggal']->format('d/m/Y') }}</label>
                  <select id="pengganti-{{ $day['tanggal']->format('Ymd') }}" name="anggota_id" required>
                    <option value="">Pilih nama anggota pengganti</option>
                    @foreach ($activeMembers as $member)
                      <option value="{{ $member->id }}">{{ $member->nama_lengkap }}</option>
                    @endforeach
                  </select>
                  <button type="submit" class="btn-primary">Tugaskan pengganti</button>
                </form>
              @elseif ($day['anggota'] < $day['kebutuhan'])
                <span class="text-warn">Masih diperlukan {{ $day['kebutuhan'] - $day['anggota'] }} anggota untuk memenuhi kebutuhan harian.</span>
              @else
                <span class="badge">Kuota harian terpenuhi</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<h3 class="section-title">Anggota yang Ditugaskan</h3>
<p class="section-note">Untuk menyetujui atau menolak laporan, buka detail anggota yang menunggu pemeriksaan. Edit penugasan hanya tersedia selama tugas masih aktif.</p>
<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Anggota</th>
          <th>Rentang Penugasan</th>
          <th>Status Tugas</th>
          <th>Presensi per Hari</th>
          <th>Upah</th>
          <th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($jadwal->penugasan->where('status', '!=', 'cancelled') as $item)
          <tr>
            <td><span class="cell-name">{{ $item->anggota->nama_lengkap }}</span></td>
            <td class="cell-num">{{ $item->tanggalMulaiEfektif()->format('d/m/Y') }} – {{ $item->tanggalSelesaiEfektif()->format('d/m/Y') }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $item->status])</td>
            <td class="cell-wrap">{{ $item->presensiHarian->map(fn ($presence) => $presence->tanggal->format('d/m').' '.ucfirst(str_replace('_', ' ', $presence->status)))->implode(', ') ?: 'Belum dicatat' }}</td>
            <td>
              @if ($item->upah)
                Rp {{ number_format((float) $item->upah->jumlah_upah, 0, ',', '.') }} | {{ str_replace('_', ' ', $item->upah->status_pembayaran) }}
              @elseif ($item->status === 'completed')
                Upah belum tersedia
              @else
                Menunggu pekerjaan selesai
              @endif
            </td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.penugasan.show', $item) }}" class="table-action">Detail</a>
                @if (in_array($item->status, ['assigned', 'in_progress'], true))
                  <a href="{{ route('admin.penugasan.edit', $item) }}" class="table-action">Edit</a>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty-state">Belum ada anggota yang ditugaskan.</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if ($availableSlots > 0)
  <div id="tambah-anggota" class="panel panel-body">
    <h3>Tambah Anggota</h3>
    <p class="note">Cari anggota untuk mengisi {{ $availableSlots }} posisi yang tersisa.</p>
    <form method="post" action="{{ route('admin.penugasan.store') }}">
      @csrf
      <input type="hidden" name="jadwal_id" value="{{ $jadwal->id }}">
      <div class="field">
        <label for="anggota-search">Cari anggota</label>
        <input id="anggota-search" type="search" placeholder="Ketik nama atau nomor anggota" autocomplete="off">
      </div>
      <div class="field">
        <label for="anggota_id">Pilih anggota</label>
        <select id="anggota_id" name="anggota_id" class="@error('anggota_id') is-invalid @enderror" required>
          <option value="">Pilih anggota aktif yang jadwalnya tersedia</option>
          @foreach ($availableMembers as $member)
            <option value="{{ $member['anggota_id'] }}" @selected(old('anggota_id') == $member['anggota_id'])>{{ $member['nama_lengkap'] }} ({{ $member['nomor_anggota'] }}), {{ $member['jumlah_tugas_aktif'] }} tugas aktif, {{ $member['jumlah_tugas_selesai'] }} tugas selesai, {{ $member['tingkat_kemampuan'] ? 'kompetensi '.$member['tingkat_kemampuan'] : 'kompetensi belum dicatat' }}</option>
          @endforeach
        </select>
        @error('anggota_id') <span class="error">{{ $message }}</span> @enderror
        @if (empty($availableMembers))
          <span class="hint">Tidak ada anggota aktif dengan jadwal yang tersedia pada waktu ini.</span>
        @endif
      </div>
      <button type="submit" class="btn-primary btn-auto" @disabled(count($availableMembers) === 0)>Tambah Anggota</button>
    </form>
  </div>
@endif
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