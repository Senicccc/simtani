@extends('layouts.backend')
@section('title', 'Edit Jenis Pekerjaan')

@section('content')
<div class="page-head">
  <div>
    <h2>Edit Jenis Pekerjaan</h2>
    <p>{{ $jenisPekerjaan->nama_pekerjaan }}</p>
  </div>
  <a href="{{ route('admin.jenis-pekerjaan.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.jenis-pekerjaan.update', $jenisPekerjaan) }}" class="form-page panel">
  @csrf
  @method('PUT')
  @include('admin.jenis-pekerjaan.form')
  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
    <a href="{{ route('admin.jenis-pekerjaan.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection