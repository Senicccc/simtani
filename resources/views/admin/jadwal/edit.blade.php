@extends('layouts.backend')
@section('title', 'Edit Jadwal')

@section('content')
<div class="page-head">
  <div>
    <h2>Edit Jadwal</h2>
    <p>{{ $jadwal->nama_kegiatan }}</p>
  </div>
  <a href="{{ route('admin.jadwal.show', $jadwal) }}" class="btn-outline">Kembali ke detail</a>
</div>

<form method="post" action="{{ route('admin.jadwal.update', $jadwal) }}" class="form-page panel">
  @csrf
  @method('PUT')
  @include('admin.jadwal.form')
  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
    <a href="{{ route('admin.jadwal.show', $jadwal) }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection