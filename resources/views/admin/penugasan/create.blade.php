@extends('layouts.backend')
@section('title', 'Tambah Penugasan')
@section('content')
<h2>Tambah Penugasan Manual</h2>
<form method="post" action="{{ route('admin.penugasan.store') }}">@csrf
<label>Kegiatan <select name="jadwal_id" required><option value="">Pilih kegiatan</option>@foreach($jadwal as $item)<option value="{{ $item->id }}" @selected(old('jadwal_id') == $item->id)>{{ $item->nama_kegiatan }} - {{ $item->tanggal }} ({{ $item->jenisPekerjaan->nama_pekerjaan }})</option>@endforeach</select></label>
<label>Anggota <select name="anggota_id" required><option value="">Pilih anggota</option>@foreach($anggota as $item)<option value="{{ $item->id }}" @selected(old('anggota_id') == $item->id)>{{ $item->nama_lengkap }}</option>@endforeach</select></label>
<button type="submit">Simpan penugasan</button></form>
<p>Penjadwalan otomatis: <strong>Coming soon</strong></p>
@endsection