@extends('layouts.backend')
@section('title', 'Tambah Penugasan')

@section('content')
<div class="page-head">
  <div>
    <h2>Tambah Penugasan Manual</h2>
    <p>Untuk membuat penugasan otomatis, pilih jadwal lalu buka halaman detail jadwal.</p>
  </div>
  <a href="{{ route('admin.penugasan.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.penugasan.store') }}" class="form-page panel">
  @csrf
  <div class="field">
    <label for="jadwal_id">Kegiatan</label>
    <select id="jadwal_id" name="jadwal_id" class="@error('jadwal_id') is-invalid @enderror" required>
      <option value="">Pilih kegiatan</option>
      @foreach ($jadwal as $item)
        <option value="{{ $item->id }}" @selected(old('jadwal_id') == $item->id)>{{ $item->nama_kegiatan }} - {{ $item->tanggal?->format('d-m-Y') }} ({{ $item->jenisPekerjaan->nama_pekerjaan }})</option>
      @endforeach
    </select>
    @error('jadwal_id') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="anggota_id">Anggota</label>
    <select id="anggota_id" name="anggota_id" class="@error('anggota_id') is-invalid @enderror" required>
      <option value="">Pilih anggota</option>
      @foreach ($anggota as $item)
        <option value="{{ $item->id }}" @selected(old('anggota_id') == $item->id)>{{ $item->nama_lengkap }}</option>
      @endforeach
    </select>
    @error('anggota_id') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Penugasan</button>
    <a href="{{ route('admin.penugasan.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection
