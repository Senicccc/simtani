@extends('layouts.backend')
@section('title', 'Edit Presensi')

@section('content')
<div class="page-head">
  <div>
    <h2>Edit Presensi</h2>
    <p class="meta-row">
      <span>{{ $presensi->penugasan->jadwal->nama_kegiatan }}</span>
      <span>{{ $presensi->penugasan->anggota->nama_lengkap }}</span>
    </p>
  </div>
  <a href="{{ route('admin.presensi.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.presensi.update', $presensi) }}" class="form-page panel">
  @csrf
  @method('PUT')

  <div class="form-row">
    <div class="field">
      <label for="waktu_check_in">Check-in</label>
      <input id="waktu_check_in" type="datetime-local" name="waktu_check_in"
             value="{{ old('waktu_check_in', $presensi->waktu_check_in?->format('Y-m-d\TH:i')) }}"
             class="@error('waktu_check_in') is-invalid @enderror">
      @error('waktu_check_in') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="field">
      <label for="waktu_check_out">Check-out</label>
      <input id="waktu_check_out" type="datetime-local" name="waktu_check_out"
             value="{{ old('waktu_check_out', $presensi->waktu_check_out?->format('Y-m-d\TH:i')) }}"
             class="@error('waktu_check_out') is-invalid @enderror">
      @error('waktu_check_out') <span class="error">{{ $message }}</span> @enderror
    </div>
  </div>

  <div class="field">
    <label for="status">Status</label>
    <select id="status" name="status" class="@error('status') is-invalid @enderror">
      @foreach (['hadir' => 'Hadir', 'tidak_hadir' => 'Tidak hadir', 'izin' => 'Izin'] as $value => $label)
        <option value="{{ $value }}" @selected(old('status', $presensi->status) === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('status') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="catatan">Catatan</label>
    <textarea id="catatan" name="catatan" class="@error('catatan') is-invalid @enderror">{{ old('catatan', $presensi->catatan) }}</textarea>
    @error('catatan') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
    <a href="{{ route('admin.presensi.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection