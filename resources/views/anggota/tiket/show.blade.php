@extends('layouts.backend')
@section('title', 'Detail Tiket')

@section('content')
<div class="page-head">
  <div>
    <h2>{{ $tiket->subjek }}</h2>
    <p>{{ $tiket->kategori }} · @include('admin.partials.status-badge', ['type' => 'tiket', 'status' => $tiket->status])</p>
  </div>
  <a href="{{ route('anggota.tiket.index') }}" class="btn-outline">Kembali</a>
</div>

<div class="panel panel-body">
  <ul class="thread">
    @foreach ($tiket->pesan as $pesan)
      <li class="msg {{ $pesan->is_admin ? 'is-admin' : '' }}">
        <div class="msg-meta">
          <span>{{ $pesan->is_admin ? 'Admin' : 'Anda' }}</span>
          <span>{{ $pesan->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <p class="msg-body">{{ $pesan->isi }}</p>
      </li>
    @endforeach
  </ul>
</div>

<div class="panel panel-body">
  @if ($tiket->status === 'resolved')
    <p class="note">Tiket ini sudah selesai. Jika kendalanya muncul lagi, buat tiket baru.</p>
    <a href="{{ route('anggota.tiket.create') }}" class="btn-outline">Buat Tiket Baru</a>
  @else
    <form method="post" action="{{ route('anggota.tiket.balas', $tiket) }}">
      @csrf
      <div class="field">
        <label for="isi">Tambah pesan</label>
        <textarea id="isi" name="isi" rows="4" maxlength="2000" class="@error('isi') is-invalid @enderror" required>{{ old('isi') }}</textarea>
        @error('isi') <span class="error">{{ $message }}</span> @enderror
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-primary btn-auto">Kirim</button>
      </div>
    </form>
  @endif
</div>
@endsection