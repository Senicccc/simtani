@extends('layouts.backend')
@section('title', 'Buat Tiket')

@section('content')
<div class="page-head">
  <div>
    <h2>Buat Tiket</h2>
    <p>Sampaikan kendala Anda. Admin akan membalas di halaman tiket.</p>
  </div>
  <a href="{{ route('anggota.tiket.index') }}" class="btn-outline">Kembali</a>
</div>

<form method="post" action="{{ route('anggota.tiket.store') }}" class="form-page panel panel-body">
  @csrf
  <div class="field">
    <label for="kategori">Kategori</label>
    <select id="kategori" name="kategori" class="@error('kategori') is-invalid @enderror" required>
      <option value="">Pilih kategori</option>
      @foreach (['Tugas & jadwal', 'Presensi', 'Upah', 'Akun & profil', 'Lainnya'] as $k)
        <option value="{{ $k }}" @selected(old('kategori') === $k)>{{ $k }}</option>
      @endforeach
    </select>
    @error('kategori') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="subjek">Subjek</label>
    <input id="subjek" name="subjek" type="text" maxlength="120" value="{{ old('subjek') }}"
           class="@error('subjek') is-invalid @enderror" required>
    @error('subjek') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="isi">Pesan</label>
    <textarea id="isi" name="isi" rows="5" maxlength="2000" class="@error('isi') is-invalid @enderror" required>{{ old('isi') }}</textarea>
    @error('isi')
      <span class="error">{{ $message }}</span>
    @else
      <span class="hint">Jelaskan kendalanya, sebutkan nama kegiatan atau tanggal jika ada.</span>
    @enderror
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Kirim Tiket</button>
    <a href="{{ route('anggota.tiket.index') }}" class="btn-outline">Batal</a>
  </div>
</form>
@endsection