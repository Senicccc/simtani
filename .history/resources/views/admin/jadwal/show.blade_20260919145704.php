@extends('layouts.backend')
@section('title', 'Detail Jadwal')
@section('content')<h2>{{ $jadwal->nama_kegiatan }}</h2><p>{{ $jadwal->deskripsi }}</p><dl><dt>Tanggal</dt><dd>{{ $jadwal->tanggal }}</dd><dt>Lokasi</dt><dd>{{ $jadwal->lokasi }}</dd><dt>Status</dt><dd>{{ $jadwal->status }}</dd></dl><p><a href="{{ route('admin.jadwal.edit', $jadwal) }}">Edit</a> <a href="{{ route('admin.jadwal.penugasan', $jadwal) }}">Lihat penugasan</a></p><form method="post" action="{{ route('admin.jadwal.generate', $jadwal) }}">@csrf<button type="submit">Generate penugasan otomatis</button></form>@endsection
