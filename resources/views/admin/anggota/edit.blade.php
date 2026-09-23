@extends('layouts.backend')
@section('title', 'Edit Anggota')
@section('content')
<h2>Edit Anggota</h2>
<form method="post" action="{{ route('admin.anggota.update', $anggota) }}" class="form-page">
  @csrf
  @method('PUT')
  @include('admin.anggota.form')
  <div class="form-actions">
    <button type="submit" class="btn-primary" style="width:auto;padding:10px 20px">Simpan perubahan</button>
    <a href="{{ route('admin.anggota.index') }}" class="btn-outline" style="display:inline-flex;align-items:center;text-decoration:none">Batal</a>
  </div>
</form>
@endsection
