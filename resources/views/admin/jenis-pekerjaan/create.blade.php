@extends('layouts.backend')
@section('title', 'Tambah Jenis Pekerjaan')
@section('content')<h2>Tambah Jenis Pekerjaan</h2><form method="post" action="{{ route('admin.jenis-pekerjaan.store') }}">@csrf @include('admin.jenis-pekerjaan.form')<button type="submit">Simpan</button></form>@endsection
