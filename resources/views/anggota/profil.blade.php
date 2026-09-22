@extends('layouts.backend')
@section('title', 'Profil Saya')
@section('content')<h2>Profil Saya</h2><form method="post" action="{{ route('anggota.profil.update') }}">@csrf @method('PUT')<label>Nama <input name="nama_lengkap" value="{{ $user->nama_lengkap }}" required></label><label>Email <input type="email" name="email" value="{{ $user->email }}" required></label><label>Nomor WhatsApp <input name="nomor_wa" value="{{ $user->nomor_wa }}"></label><label>Alamat <textarea name="alamat">{{ $user->alamat }}</textarea></label><button type="submit">Simpan</button></form>@endsection
