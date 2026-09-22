@extends('layouts.backend')
@section('title', 'Edit Jadwal')
@section('content')<h2>Edit Jadwal</h2><form method="post" action="{{ route('admin.jadwal.update', $jadwal) }}">@csrf @method('PUT') @include('admin.jadwal.form')<button type="submit">Simpan perubahan</button></form>@endsection
