@extends('layouts.backend')
@section('title', 'Edit Presensi')
@section('content')
<h2>Edit Presensi</h2><p>{{ $presensi->penugasan->jadwal->nama_kegiatan }} - {{ $presensi->penugasan->anggota->nama_lengkap }}</p>
<form method="post" action="{{ route('admin.presensi.update', $presensi) }}">@csrf @method('PUT')
<label>Check-in <input type="datetime-local" name="waktu_check_in" value="{{ old('waktu_check_in', optional($presensi->waktu_check_in)->format('Y-m-d\TH:i')) }}"></label><label>Check-out <input type="datetime-local" name="waktu_check_out" value="{{ old('waktu_check_out', optional($presensi->waktu_check_out)->format('Y-m-d\TH:i')) }}"></label>
<label>Status <select name="status"><option value="hadir" @selected($presensi->status === 'hadir')>Hadir</option><option value="tidak_hadir" @selected($presensi->status === 'tidak_hadir')>Tidak hadir</option><option value="izin" @selected($presensi->status === 'izin')>Izin</option></select></label><label>Catatan <textarea name="catatan">{{ old('catatan', $presensi->catatan) }}</textarea></label><button type="submit">Simpan</button></form>
@endsection