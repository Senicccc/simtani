@extends('layouts.backend')
@section('title', 'Edit Penugasan')

@section('content')
<div class="page-head">
  <div>
    <h2>Ubah Penugasan</h2>
    <p>{{ $penugasan->jadwal->nama_kegiatan }}</p>
  </div>
  <a href="{{ route('admin.penugasan.show', $penugasan) }}" class="btn-outline">Kembali ke detail tugas</a>
</div>

<form method="post" action="{{ route('admin.penugasan.update', $penugasan) }}" class="form-page panel">
  @csrf
  @method('PUT')

  <div class="field">
    <label for="anggota_id">Anggota</label>
    <select id="anggota_id" name="anggota_id" class="@error('anggota_id') is-invalid @enderror" required>
      @foreach ($anggota as $item)
        <option value="{{ $item->id }}" @selected(old('anggota_id', $penugasan->anggota_id) == $item->id)>{{ $item->nama_lengkap }} ({{ $item->nomor_anggota }})</option>
      @endforeach
    </select>
    @error('anggota_id') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <span class="field-label">Status tugas</span>
    @include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $penugasan->status])
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
    <a href="{{ route('admin.penugasan.show', $penugasan) }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection
