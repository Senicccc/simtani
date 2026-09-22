@extends('layouts.backend')
@section('title', 'Tambah Presensi')
@section('content')
<h2>Tambah Presensi</h2>
<form method="post" action="{{ route('admin.presensi.store') }}">@csrf
<label>Penugasan <select name="penugasan_id" required><option value="">Pilih penugasan</option>@foreach($penugasan as $item)<option value="{{ $item->id }}">{{ $item->jadwal->nama_kegiatan }} - {{ $item->anggota->nama_lengkap }}</option>@endforeach</select></label>
<label>Check-in <input type="datetime-local" name="waktu_check_in" value="{{ old('waktu_check_in') }}"></label><label>Check-out <input type="datetime-local" name="waktu_check_out" value="{{ old('waktu_check_out') }}"></label>
<label>Status <select name="status"><option value="hadir">Hadir</option><option value="tidak_hadir">Tidak hadir</option><option value="izin">Izin</option></select></label><label>Catatan <textarea name="catatan">{{ old('catatan') }}</textarea></label><button type="submit">Simpan</button></form>
@endsection