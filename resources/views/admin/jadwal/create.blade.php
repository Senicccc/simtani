@extends('layouts.backend')
@section('title', 'Tambah Jadwal')

@section('content')
<div class="page-head">
  <div>
    <h2>Tambah Jadwal</h2>
    <p>Isi kegiatan, waktu, dan kebutuhan anggota.</p>
  </div>
  <a href="{{ route('admin.jadwal.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.jadwal.store') }}" class="form-page panel">
  @csrf
  @include('admin.jadwal.form', ['jadwal' => null])
  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Jadwal</button>
    <a href="{{ route('admin.jadwal.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection