@extends('layouts.backend')
@section('title', 'Detail Penugasan')
@section('content')
<div class="view-header">
  <h2 style="margin:0">{{ $penugasan->jadwal->nama_kegiatan }}</h2>
  <a href="{{ route('admin.jadwal.show', $penugasan->jadwal) }}" class="btn-outline">Kembali ke detail jadwal</a>
</div>

<p>Jenis pekerjaan: {{ $penugasan->jadwal->jenisPekerjaan->nama_pekerjaan }}</p>
<p>Jadwal: {{ $penugasan->tanggalMulaiEfektif()->format('d-m-Y') }} – {{ $penugasan->tanggalSelesaiEfektif()->format('d-m-Y') }} | {{ $penugasan->jadwal->waktu_mulai }} - {{ $penugasan->jadwal->waktu_selesai }} | {{ $penugasan->jadwal->lokasi }}</p>
<p>Total upah kegiatan: Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }}. Bagian per anggota dihitung rata setelah semua tugas selesai.</p>
<p>Penyelesaian: {{ $penugasan->jadwal->perlu_verifikasi ? 'Admin memeriksa laporan sebelum menyetujui upah.' : 'Tugas selesai otomatis saat anggota mengirim laporan.' }}</p>
@if($penugasan->status === 'completed')
  <p><a href="{{ route('admin.upah.index', ['jadwal_id' => $penugasan->jadwal_id]) }}" class="btn-primary attendance-button">Lihat upah kegiatan ini</a></p>
@endif
<p><a href="{{ route('admin.jadwal.edit', $penugasan->jadwal) }}" class="table-action">Ubah detail tugas</a></p>

<h3>Daftar anggota dan laporan</h3>
<div class="table-scroll">
  <table>
    <thead><tr><th>Anggota</th><th>Status tugas</th><th>Kehadiran</th><th>Upah</th><th>Laporan selesai</th><th>Aksi</th></tr></thead>
    <tbody>
      @foreach($penugasan->jadwal->penugasan->where('status', '!=', 'cancelled') as $item)
        <tr>
          <td>{{ $item->anggota->nama_lengkap }}</td>
          <td>{{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td>
          <td>{{ $item->presensiHarian->map(fn ($presence) => $presence->tanggal->format('d-m').' '.ucfirst(str_replace('_', ' ', $presence->status)))->implode(', ') ?: 'Belum dicatat' }}</td>
          <td>
            @if($item->upah)
              Rp {{ number_format((float) $item->upah->jumlah_upah, 0, ',', '.') }}. {{ $item->upah->status_pembayaran === 'sudah_dibayar' ? 'Sudah dibayar' : 'Belum dibayar' }}
            @else
              Menunggu semua tugas anggota selesai agar total upah dapat dibagi rata.
            @endif
          </td>
          <td>
            {{ $item->catatan_penyelesaian ?: 'Belum ada laporan.' }}
            @if($item->bukti_selesai)
              <br><a href="{{ asset('storage/'.$item->bukti_selesai) }}" target="_blank" rel="noopener">Lihat bukti</a>
            @endif
          </td>
          <td>
            @if(in_array($item->status, ['assigned', 'in_progress'], true))
              <a href="{{ route('admin.penugasan.edit', $item) }}" class="table-action">Edit penugasan</a>
            @endif
            @if($item->status === 'waiting_verification')
              <form method="post" action="{{ route('admin.penugasan.verify', $item) }}" onsubmit="return confirm('Setujui laporan? Upah dibagi setelah semua anggota selesai.')">
                @csrf
                <button type="submit">Setujui laporan</button>
              </form>
            @endif
            @if(in_array($item->status, ['assigned', 'in_progress'], true))
              <form method="post" action="{{ route('admin.penugasan.remove', $item) }}">
                @csrf
                <label>Ganti dengan
                  <select name="replacement_anggota_id">
                    <option value="">Tanpa pengganti (jika kuota tetap terpenuhi)</option>
                    @foreach($anggotaPengganti as $candidate)
                      <option value="{{ $candidate->id }}">{{ $candidate->nama_lengkap }}</option>
                    @endforeach
                  </select>
                </label>
                <button type="submit">Keluarkan anggota</button>
              </form>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
<p><a href="{{ route('admin.jadwal.penugasan', $penugasan->jadwal) }}">Kelola atau tambah anggota</a></p>
@endsection
