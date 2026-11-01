@extends('layouts.backend')
@section('title', 'Edit Anggota')
@section('content')
<div class="page-head">
  <div>
    <h2>Edit Anggota</h2>
    <p>{{ $anggota->nomor_anggota }}</p>
  </div>
  <a href="{{ route('admin.anggota.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<form method="post" action="{{ route('admin.anggota.kompetensi.update', $anggota) }}" class="form-page panel panel-body">
  @csrf
  @method('PUT')
  <h3>Kompetensi Pekerjaan</h3>
  <p class="note">Tingkat kemampuan dipakai penjadwalan otomatis untuk merekomendasikan anggota. Pilih "Belum dicatat" untuk menghapus kompetensi.</p>

  @forelse ($jenisPekerjaan as $jenis)
    <div class="field kompetensi-row">
      <label for="kompetensi-{{ $jenis->id }}">{{ $jenis->nama_pekerjaan }}</label>
      <select id="kompetensi-{{ $jenis->id }}" name="kompetensi[{{ $jenis->id }}]">
        <option value="">Belum dicatat</option>
        @foreach (['pemula' => 'Pemula', 'menengah' => 'Menengah', 'mahir' => 'Mahir'] as $nilai => $label)
          <option value="{{ $nilai }}" @selected(old('kompetensi.'.$jenis->id, $kompetensi[$jenis->id] ?? null) === $nilai)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
  @empty
    <p class="note">Belum ada jenis pekerjaan. <a href="{{ route('admin.jenis-pekerjaan.create') }}">Tambahkan jenis pekerjaan</a> terlebih dahulu.</p>
  @endforelse

  @if ($jenisPekerjaan->isNotEmpty())
    <div class="form-actions">
      <button type="submit" class="btn-primary">Simpan Kompetensi</button>
    </div>
  @endif
</form>
@endsection
