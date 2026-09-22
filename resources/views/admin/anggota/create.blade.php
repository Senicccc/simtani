@extends('layouts.backend')
@section('title', 'Tambah Anggota')
@section('content')
<h2>Tambah Anggota</h2>
<form method="post" action="{{ route('admin.anggota.store') }}" class="form-page">
  @csrf
  @include('admin.anggota.form', ['anggota' => null])
  <div class="form-actions">
    <button type="submit" class="btn-primary" style="width:auto;padding:10px 20px">Simpan</button>
    <a href="{{ route('admin.anggota.index') }}" class="btn-outline" style="display:inline-flex;align-items:center;text-decoration:none">Batal</a>
  </div>
</form>
@endsection
