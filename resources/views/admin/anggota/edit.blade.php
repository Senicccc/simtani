@extends('layouts.backend')
@section('title', 'Edit Anggota')
@section('content')
<div class="page-head">
  <div>
    <h2>Edit Anggota</h2>
    <p>{{ $anggota->nomor_anggota }}</p>
  </div>
  <a href="{{ route('admin.anggota.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.anggota.update', $anggota) }}" class="form-page panel">
  @csrf
  @method('PUT')
  @include('admin.anggota.form')
  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
    <a href="{{ route('admin.anggota.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection
