@extends('layouts.backend')
@section('title', 'Selesaikan Tugas')
@section('content')
<h2>Laporkan Tugas Selesai</h2>
<p>{{ $penugasan->jadwal->nama_kegiatan }} | {{ $penugasan->tanggalMulaiEfektif()->format('d-m-Y') }} – {{ $penugasan->tanggalSelesaiEfektif()->format('d-m-Y') }}</p>
@if($penugasan->jadwal->perlu_verifikasi)
  <p>Laporan akan diperiksa admin. Total upah kegiatan Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }} dibagi rata setelah semua tugas selesai dan disetujui.</p>
@else
  <p>Tugas selesai otomatis setelah laporan dikirim. Total upah kegiatan Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }} dibagi rata setelah semua anggota selesai.</p>
@endif
<form method="post" action="{{ route('anggota.tugas.complete', $penugasan) }}" enctype="multipart/form-data" class="form-page">
  @csrf
  <label>Tanggal selesai
    <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', today()->toDateString()) }}" min="{{ $penugasan->tanggalMulaiEfektif()->toDateString() }}" max="{{ min(today()->toDateString(), $penugasan->tanggalSelesaiEfektif()->toDateString()) }}" required>
  </label>
  <label>Catatan (boleh dikosongkan)
    <textarea name="catatan_penyelesaian">{{ old('catatan_penyelesaian') }}</textarea>
  </label>
  <label>Bukti foto (boleh dikosongkan)
    <input type="file" name="bukti_selesai" accept=".jpg,.jpeg,.png,.webp,.pdf">
  </label>
  <button type="submit" class="btn-primary">{{ $penugasan->jadwal->perlu_verifikasi ? 'Kirim untuk diperiksa' : 'Laporkan dan selesaikan tugas' }}</button>
</form>
@endsection
