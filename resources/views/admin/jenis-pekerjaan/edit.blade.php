@extends('layouts.backend')
@section('title', 'Edit Jenis Pekerjaan')
@section('content')<h2>Edit Jenis Pekerjaan</h2><form method="post" action="{{ route('admin.jenis-pekerjaan.update', $jenisPekerjaan) }}">@csrf @method('PUT') @include('admin.jenis-pekerjaan.form')<button type="submit">Simpan perubahan</button></form>@endsection
