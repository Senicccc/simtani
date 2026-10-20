@extends('layouts.backend')
@section('title', 'Tambah Presensi')

@section('content')
<div class="page-head">
  <div>
    <h2>Tambah Presensi</h2>
    <p>Catat kehadiran anggota pada sebuah penugasan.</p>
  </div>
  <a href="{{ route('admin.presensi.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.presensi.store') }}" class="form-page panel">
  @csrf

  <div class="field">
    <label for="penugasan_id">Penugasan</label>
    <select id="penugasan_id" name="penugasan_id" class="@error('penugasan_id') is-invalid @enderror" required>
      <option value="">Pilih penugasan</option>
      @foreach ($penugasan as $item)
        <option value="{{ $item->id }}" @selected(old('penugasan_id') == $item->id)>{{ $item->jadwal->nama_kegiatan }} - {{ $item->anggota->nama_lengkap }}</option>
      @endforeach
    </select>
    @error('penugasan_id') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="form-row">
    <div class="field">
      <label for="waktu_check_in">Check-in</label>
      <input id="waktu_check_in" type="datetime-local" name="waktu_check_in" value="{{ old('waktu_check_in') }}"
             class="@error('waktu_check_in') is-invalid @enderror">
      @error('waktu_check_in') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="field">
      <label for="waktu_check_out">Check-out</label>
      <input id="waktu_check_out" type="datetime-local" name="waktu_check_out" value="{{ old('waktu_check_out') }}"
             class="@error('waktu_check_out') is-invalid @enderror">
      @error('waktu_check_out') <span class="error">{{ $message }}</span> @enderror
    </div>
  </div>

  <div class="field">
    <label for="status">Status</label>
    <select id="status" name="status" class="@error('status') is-invalid @enderror">
      @foreach (['hadir' => 'Hadir', 'tidak_hadir' => 'Tidak hadir', 'izin' => 'Izin'] as $value => $label)
        <option value="{{ $value }}" @selected(old('status', 'hadir') === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('status') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="catatan">Catatan</label>
    <textarea id="catatan" name="catatan" class="@error('catatan') is-invalid @enderror">{{ old('catatan') }}</textarea>
    @error('catatan') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Presensi</button>
    <a href="{{ route('admin.presensi.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection
