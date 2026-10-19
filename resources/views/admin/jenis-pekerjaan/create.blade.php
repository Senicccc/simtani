@extends('layouts.backend')
@section('title', 'Tambah Jenis Pekerjaan')

@section('content')
<div class="page-head">
  <div>
    <h2>Tambah Jenis Pekerjaan</h2>
    <p>Satuan dipakai saat menghitung jumlah pekerjaan di jadwal.</p>
  </div>
  <a href="{{ route('admin.jenis-pekerjaan.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.jenis-pekerjaan.store') }}" class="form-page panel">
  @csrf
  @include('admin.jenis-pekerjaan.form')
  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Jenis Pekerjaan</button>
    <a href="{{ route('admin.jenis-pekerjaan.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection