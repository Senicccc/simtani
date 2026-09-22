@extends('layouts.backend')
@section('title', 'Tambah Anggota')
@section('content')
<h2>Tambah Anggota</h2>
<form method="post" action="{{ route('admin.anggota.store') }}">@csrf
@include('admin.anggota.form', ['anggota' => null])
<button type="submit">Simpan</button>
</form>
@endsection
