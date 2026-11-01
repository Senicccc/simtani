@extends('layouts.backend')
@section('title', 'Detail Tiket')

@section('content')
<div class="page-head">
  <div>
    <h2>{{ $tiket->subjek }}</h2>
    <p>@include('admin.partials.status-badge', ['type' => 'tiket', 'status' => $tiket->status])</p>
  </div>
  <div class="page-actions">
    <a href="{{ route('admin.tiket.index') }}" class="btn-outline">Kembali ke daftar</a>
  </div>
</div>

<div class="panel panel-body">
  <dl class="detail-grid">
    <div><dt>Pengirim</dt><dd>{{ $tiket->anggota->nama_lengkap }}<small>{{ $tiket->anggota->nomor_anggota }}</small></dd></div>
    <div><dt>Kategori</dt><dd>{{ $tiket->kategori }}</dd></div>
    <div><dt>Dikirim</dt><dd>{{ $tiket->created_at->format('d/m/Y H:i') }}</dd></div>
    <div><dt>Diperbarui</dt><dd>{{ $tiket->updated_at->format('d/m/Y H:i') }}</dd></div>
  </dl>
</div>

<h3 class="section-title">Percakapan</h3>
<div class="panel panel-body">
  <ul class="thread">
    @foreach ($tiket->pesan as $pesan)
      <li class="msg {{ $pesan->is_admin ? 'is-admin' : '' }}">
        <div class="msg-meta">
          <span>{{ $pesan->is_admin ? 'Admin' : $tiket->anggota->nama_lengkap }}</span>
          <span>{{ $pesan->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <p class="msg-body">{{ $pesan->isi }}</p>
      </li>
    @endforeach
  </ul>
</div>

<h3 class="section-title">Status Tiket</h3>
<div class="panel panel-body">
  <form method="post" action="{{ route('admin.tiket.status', $tiket) }}" class="inline-form">
    @csrf
    @method('PATCH')
    <label for="status" class="sr-only">Status</label>
    <select id="status" name="status">
      @foreach (['open' => 'Terbuka', 'in_progress' => 'Diproses', 'resolved' => 'Selesai'] as $nilai => $label)
        <option value="{{ $nilai }}" @selected($tiket->status === $nilai)>{{ $label }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn-primary">Simpan Status</button>
  </form>
</div>

<h3 class="section-title">Balas</h3>
<div class="panel panel-body">
  @if ($tiket->status === 'resolved')
    <p class="note">Tiket sudah selesai. Ubah status ke Terbuka atau Diproses jika perlu membalas lagi.</p>
  @else
    <form method="post" action="{{ route('admin.tiket.balas', $tiket) }}">
      @csrf
      <div class="field">
        <label for="isi">Balasan</label>
        <textarea id="isi" name="isi" rows="4" maxlength="2000" class="@error('isi') is-invalid @enderror" required>{{ old('isi') }}</textarea>
        @error('isi') <span class="error">{{ $message }}</span> @enderror
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-primary btn-auto">Kirim Balasan</button>
      </div>
    </form>
  @endif
</div>
@endsection