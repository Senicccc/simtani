@extends('layouts.backend')
@section('title', 'Edit Anggota')
@section('content')
<h2>Edit Anggota</h2>
<form method="post" action="{{ route('admin.anggota.update', $anggota) }}">@csrf @method('PUT')
@include('admin.anggota.form')
<button type="submit">Simpan perubahan</button>
</form>
@endsection
