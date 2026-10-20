@extends('layouts.backend')
@section('title', 'Profil Admin')

@section('content')
<div class="page-head">
  <div>
    <h2>Profil Admin</h2>
    <p>Perbarui data akun Anda.</p>
  </div>
</div>

<form method="post" action="{{ route('admin.profil.update') }}" class="form-page panel">
  @csrf
  @method('PUT')

  <div class="form-section">
    <h3>Data Diri</h3>
    <div class="form-row">
      <div class="field">
        <label for="nama_lengkap">Nama</label>
        <input id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $user->nama_lengkap) }}"
               autocomplete="name" class="@error('nama_lengkap') is-invalid @enderror" required>
        @error('nama_lengkap') <span class="error">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
               autocomplete="email" class="@error('email') is-invalid @enderror" required>
        @error('email') <span class="error">{{ $message }}</span> @enderror
      </div>
    </div>
  </div>

  <div class="form-section">
    <h3>Kontak</h3>
    <div class="form-row">
      <div class="field">
        <label for="nomor_wa">Nomor WhatsApp</label>
        <input id="nomor_wa" name="nomor_wa" value="{{ old('nomor_wa', $user->nomor_wa) }}"
               inputmode="tel" autocomplete="tel" class="@error('nomor_wa') is-invalid @enderror">
        @error('nomor_wa') <span class="error">{{ $message }}</span> @enderror
      </div>
    </div>
    <div class="field">
      <label for="alamat">Alamat</label>
      <textarea id="alamat" name="alamat" class="@error('alamat') is-invalid @enderror">{{ old('alamat', $user->alamat) }}</textarea>
      @error('alamat') <span class="error">{{ $message }}</span> @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
  </div>
</form>
@endsection
