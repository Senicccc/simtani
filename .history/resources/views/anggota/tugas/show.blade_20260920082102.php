@extends('layouts.backend')
@section('title', 'Detail Tugas')
@section('content')
<h2>{{ $penugasan->jadwal->nama_kegiatan ?? 'Tugas' }}</h2>
<dl><dt>Status</dt><dd>{{ $penugasan->status }}</dd><dt>Tanggal</dt><dd>{{ $penugasan->jadwal->tanggal ?? '-' }}</dd><dt>Presensi</dt><dd>{{ $penugasan->presensi?->status ?? 'Belum ada' }}</dd></dl>
@if(!$penugasan->presensi)
<form method="post" action="{{ route('anggota.presensi.status', $penugasan) }}">@csrf<label>Status presensi <select name="status"><option value="hadir">Hadir</option><option value="izin">Izin</option><option value="tidak_hadir">Tidak hadir</option></select></label><button type="submit">Simpan presensi</button></form>
@endif
@if($rekanTugas->isNotEmpty())<h3>Anggota satu kegiatan</h3><ul>@foreach($rekanTugas as $rekan)<li>{{ $rekan->anggota->nama_lengkap }}</li>@endforeach</ul>@endif
@if($penugasan->status === 'assigned')<form method="post" action="{{ route('anggota.tugas.mulai', $penugasan) }}">@csrf<button type="submit">Mulai tugas</button></form>@endif
@if(in_array($penugasan->status, ['in_progress', 'assigned']))<a href="{{ route('anggota.tugas.complete.form', $penugasan) }}">Laporkan selesai</a>@endif
<p><a href="{{ route('anggota.hubungi-admin') }}">Hubungi admin</a></p>
@endsection
