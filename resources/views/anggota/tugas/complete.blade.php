@extends('layouts.backend')
@section('title', 'Selesaikan Tugas')
@section('content')<h2>Laporkan Tugas Selesai</h2><form method="post" action="{{ route('anggota.tugas.complete', $penugasan) }}" enctype="multipart/form-data">@csrf<label>Catatan penyelesaian <textarea name="catatan_penyelesaian"></textarea></label><label>Bukti selesai <input type="file" name="bukti_selesai"></label><button type="submit">Kirim untuk verifikasi</button></form>@endsection
