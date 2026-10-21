@extends('layouts.backend')
@section('title', 'Upah')

@section('content')
<div class="page-head">
  <div>
    <h2>Upah</h2>
    @if ($selectedSchedule)
      <p>Upah kegiatan: {{ $selectedSchedule->nama_kegiatan }}</p>
    @endif
  </div>
  @if ($selectedSchedule)
    <a href="{{ route('admin.upah.index') }}" class="btn-outline">Lihat Semua Upah</a>
  @endif
</div>

@if (! $selectedSchedule && request()->filled('jadwal_id'))
  <div class="banner mb-16">Kegiatan tidak ditemukan. Daftar berikut tidak difilter berdasarkan kegiatan.</div>
@endif

@if (! request()->filled('status_pembayaran'))
  <h3 class="section-title">{{ $selectedSchedule ? 'Laporan kegiatan yang perlu diperiksa' : 'Laporan yang perlu diperiksa' }}</h3>
  <p class="section-note">Tugas yang menunggu persetujuan admin. Setelah disetujui, upah masuk ke daftar pembayaran.</p>
  <div class="panel">
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>Anggota</th>
            <th>Tugas</th>
            <th>Tanggal Laporan</th>
            <th>Upah Disepakati</th>
            <th class="col-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($menungguVerifikasi as $penugasan)
            <tr>
              <td><span class="cell-name">{{ $penugasan->anggota->nama_lengkap }}</span></td>
              <td>{{ $penugasan->jadwal->nama_kegiatan }}</td>
              <td class="cell-num">{{ $penugasan->completed_at?->format('d/m/Y') ?? '-' }}</td>
              <td>
                Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }}
                <span class="cell-sub">total kegiatan, dibagi rata</span>
              </td>
              <td>
                <div class="row-actions">
                  <a href="{{ route('admin.penugasan.show', $penugasan) }}" class="table-action">Periksa Laporan</a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="5"><div class="empty-state">Tidak ada tugas yang menunggu pemeriksaan.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endif

<h3 class="section-title">Daftar Pembayaran</h3>
<form method="get" class="filter-bar">
  @if ($selectedSchedule)
    <input type="hidden" name="jadwal_id" value="{{ $selectedSchedule->id }}">
  @endif
  <label for="wage-status" class="sr-only">Filter pembayaran</label>
  <select id="wage-status" name="status_pembayaran">
    <option value="">Semua status</option>
    <option value="belum_dibayar" @selected(request('status_pembayaran') === 'belum_dibayar')>Belum dibayar</option>
    <option value="sudah_dibayar" @selected(request('status_pembayaran') === 'sudah_dibayar')>Sudah dibayar</option>
  </select>
  <button type="submit" class="btn-primary">Terapkan Filter</button>
  <a href="{{ route('admin.upah.index') }}" class="btn-outline">Hapus Filter</a>
</form>

<div class="panel">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Anggota</th>
          <th>Kegiatan</th>
          <th>Presensi</th>
          <th>Jumlah</th>
          <th>Status</th>
          <th>Dibayar</th>
          <th class="col-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($upah as $item)
          <tr>
            <td><span class="cell-name">{{ $item->penugasan->anggota->nama_lengkap ?? '-' }}</span></td>
            <td><a href="{{ route('admin.penugasan.show', $item->penugasan) }}" class="table-action">{{ $item->penugasan->jadwal->nama_kegiatan ?? '-' }}</a></td>
            <td>@include('admin.partials.status-badge', ['type' => 'presensi', 'status' => $item->penugasan->presensi?->status ?? 'belum'])</td>
            <td class="cell-num">Rp {{ number_format((float) $item->jumlah_upah, 0, ',', '.') }}</td>
            <td>@include('admin.partials.status-badge', ['type' => 'bayar', 'status' => $item->status_pembayaran])</td>
            <td>
              @if ($item->metode_pembayaran || $item->waktu_pembayaran)
                <span class="cell-name">{{ $item->metode_pembayaran ?? '-' }}</span>
                <span class="cell-sub">{{ $item->waktu_pembayaran?->format('d/m/Y H:i') ?? '-' }}</span>
              @else
                -
              @endif
            </td>
            <td>
              <div class="row-actions">
                <a href="{{ route('admin.upah.edit', $item) }}" class="table-action">Edit Pembayaran</a>
                @if ($item->status_pembayaran === 'belum_dibayar')
                  <form method="post" action="{{ route('admin.upah.mark-paid', $item) }}">
                    @csrf
                    <button type="submit" class="table-action">Tandai Dibayar</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7">
              <div class="empty-state">
                @if ($selectedSchedule && $menungguVerifikasi->isNotEmpty())
                  Upah belum dibagikan karena laporan kegiatan ini masih menunggu pemeriksaan admin.
                @elseif ($selectedSchedule)
                  Belum ada pembayaran untuk kegiatan ini. Upah dibagikan setelah seluruh tugas selesai dan laporan disetujui.
                @else
                  Belum ada data upah yang siap dibayar.
                @endif
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
