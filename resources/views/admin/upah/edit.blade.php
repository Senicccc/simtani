@extends('layouts.backend')
@section('title', 'Edit Upah')

@section('content')
<div class="page-head">
  <div>
    <h2>Edit Data Pembayaran</h2>
    <p class="meta-row">
      <span>{{ $upah->penugasan->jadwal->nama_kegiatan }}</span>
      <span>{{ $upah->penugasan->anggota->nama_lengkap }}</span>
    </p>
  </div>
  <a href="{{ route('admin.upah.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.upah.update', $upah) }}" class="form-page panel">
  @csrf
  @method('PUT')

  <div class="field">
    <span class="field-label">Upah yang disepakati</span>
    <span class="amount">Rp {{ number_format((float) $upah->jumlah_upah, 0, ',', '.') }}</span>
    <span class="hint">Nominal ini ditetapkan saat tugas dibuat.</span>
  </div>

  <div class="field">
    <label for="status_pembayaran">Status pembayaran</label>
    <select id="status_pembayaran" name="status_pembayaran" class="@error('status_pembayaran') is-invalid @enderror" required>
      <option value="belum_dibayar">Belum dibayar</option>
      <option value="sudah_dibayar" @selected(old('status_pembayaran', $upah->status_pembayaran) === 'sudah_dibayar')>Sudah dibayar</option>
    </select>
    @error('status_pembayaran')
      <span class="error">{{ $message }}</span>
    @else
      <span class="hint">Tanggal dan jam pembayaran dicatat otomatis saat status diubah menjadi sudah dibayar.</span>
    @enderror
  </div>

  <div class="field">
    <label for="metode_pembayaran">Metode pembayaran</label>
    <input id="metode_pembayaran" name="metode_pembayaran" placeholder="Transfer atau tunai"
           value="{{ old('metode_pembayaran', $upah->metode_pembayaran) }}"
           class="@error('metode_pembayaran') is-invalid @enderror">
    @error('metode_pembayaran') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="catatan">Catatan</label>
    <textarea id="catatan" name="catatan" class="@error('catatan') is-invalid @enderror">{{ old('catatan', $upah->catatan) }}</textarea>
    @error('catatan') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
    <a href="{{ route('admin.upah.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection
