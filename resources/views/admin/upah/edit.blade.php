@extends('layouts.backend')
@section('title', 'Edit Upah')
@section('content')
<h2>Edit Data Pembayaran</h2>
<p>Upah yang disepakati: Rp {{ number_format((float) $upah->jumlah_upah, 0, ',', '.') }}. Nominal ini ditetapkan saat tugas dibuat.</p>
<form method="post" action="{{ route('admin.upah.update', $upah) }}" class="form-page">
  @csrf
  @method('PUT')
  <label>Status pembayaran
    <select name="status_pembayaran" required>
      <option value="belum_dibayar">Belum dibayar</option>
      <option value="sudah_dibayar" @selected($upah->status_pembayaran === 'sudah_dibayar')>Sudah dibayar</option>
    </select>
  </label>
  <label>Metode pembayaran
    <input name="metode_pembayaran" placeholder="Transfer atau tunai" value="{{ old('metode_pembayaran', $upah->metode_pembayaran) }}">
  </label>
  <p>Tanggal dan jam pembayaran dicatat otomatis saat status diubah menjadi sudah dibayar.</p>
  <label>Catatan <textarea name="catatan">{{ old('catatan', $upah->catatan) }}</textarea></label>
  <button type="submit">Simpan</button>
</form>
@endsection
