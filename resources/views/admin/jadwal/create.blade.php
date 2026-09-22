@extends('layouts.backend')
@section('title', 'Tambah Jadwal')
@section('content')<h2>Tambah Jadwal</h2><form method="post" action="{{ route('admin.jadwal.store') }}">@csrf @include('admin.jadwal.form', ['jadwal' => null])<button type="submit">Simpan</button></form>@endsection
