@extends('layouts.backend')
@section('title', 'Tambah Anggota')
@section('content')
<div class="page-head">
  <div>
    <h2>Tambah Anggota</h2>
    <p>Nomor anggota dibuat otomatis saat disimpan.</p>
  </div>
  <a href="{{ route('admin.anggota.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.anggota.store') }}" class="form-page panel">
  @csrf
  @include('admin.anggota.form', ['anggota' => null])
  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Anggota</button>
    <a href="{{ route('admin.anggota.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection
