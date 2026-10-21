@extends('layouts.backend')

@section('title', 'Detail Permintaan')

@section('content')
<div class="page-head">
  <div>
    <h2>Detail Permintaan Perubahan</h2>
    <p>@include('admin.partials.status-badge', ['type' => 'permintaan', 'status' => $permintaan->status])</p>
  </div>
  <a href="{{ route('admin.permintaan-perubahan.index') }}" class="btn-outline">Kembali ke daftar</a>
</div>

<div class="panel panel-body">
  <dl class="detail-grid">
    <div>
      <dt>Pengaju</dt>
      <dd>{{ $permintaan->diajukanOleh->nama_lengkap }}</dd>
    </div>
    <div>
      <dt>Jenis</dt>
      <dd>{{ $permintaan->jenis_permintaan === 'tukar' ? 'Pindah ke jadwal lain' : 'Sanggah / tidak sanggup mengerjakan' }}</dd>
    </div>
    <div>
      <dt>Tugas pengaju</dt>
      <dd>
        <a href="{{ route('admin.penugasan.show', $permintaan->penugasan) }}">{{ $permintaan->penugasan->jadwal->nama_kegiatan }}</a>
        <small>{{ $permintaan->penugasan->jadwal->tanggal?->format('d/m/Y') }}</small>
      </dd>
    </div>

    @if ($permintaan->targetJadwal)
      <div>
        <dt>Jadwal tujuan yang diminta</dt>
        <dd>{{ $permintaan->targetJadwal->nama_kegiatan }}<small>{{ $permintaan->targetJadwal->tanggal?->format('d/m/Y') }}</small></dd>
      </div>
    @elseif ($permintaan->targetPenugasan)
      <div>
        <dt>Jadwal tujuan riwayat lama</dt>
        <dd>{{ $permintaan->targetPenugasan->jadwal->nama_kegiatan }}<small>{{ $permintaan->targetPenugasan->jadwal->tanggal?->format('d/m/Y') }}</small></dd>
      </div>
    @else
      <div>
        <dt>Pengganti yang diminta</dt>
        <dd>Tidak ada; jika disetujui, tugas akan dibatalkan tanpa pengganti.</dd>
      </div>
    @endif

    <div class="span-all">
      <dt>Alasan</dt>
      <dd>{{ $permintaan->alasan }}</dd>
    </div>

    @if ($permintaan->diprosesOleh)
      <div>
        <dt>Diproses oleh</dt>
        <dd>{{ $permintaan->diprosesOleh->nama_lengkap }}<small>{{ $permintaan->diproses_at?->format('d/m/Y H:i') }}</small></dd>
      </div>
    @endif

    @if ($permintaan->catatan_admin)
      <div class="span-all">
        <dt>Catatan admin</dt>
        <dd>{{ $permintaan->catatan_admin }}</dd>
      </div>
    @endif
  </dl>
</div>

@if ($permintaan->status === 'pending')
  <form method="post" action="{{ route('admin.permintaan-perubahan.process', $permintaan) }}" class="panel panel-body">
    @csrf
    <h3>Proses Permintaan</h3>

    @if ($permintaan->jenis_permintaan === 'sanggah')
      <div class="field">
        <label for="target_jadwal_id">Jadwal pengganti jika permintaan disetujui (opsional)</label>
        <select id="target_jadwal_id" name="target_jadwal_id" class="@error('target_jadwal_id') is-invalid @enderror">
          <option value="">Tidak ada pengganti, batalkan tugas</option>
          @foreach ($availableTargets as $target)
            <option value="{{ $target->id }}" @selected(old('target_jadwal_id', $permintaan->target_jadwal_id) == $target->id)>
              {{ $target->nama_kegiatan }} · {{ $target->tanggal?->format('d/m/Y') }} · tersedia
            </option>
          @endforeach
        </select>
        @error('target_jadwal_id') <span class="error">{{ $message }}</span> @enderror
      </div>
    @else
      <p class="note">Jika disetujui, tugas pengaju dipindahkan ke jadwal tujuan yang dipilih anggota.</p>
    @endif

    <div class="field">
      <label for="catatan_admin">Catatan admin</label>
      <textarea id="catatan_admin" name="catatan_admin" maxlength="2000" class="@error('catatan_admin') is-invalid @enderror">{{ old('catatan_admin') }}</textarea>
      @error('catatan_admin') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="form-actions">
      <button name="action" value="approve" type="submit" class="btn-primary btn-auto">
        {{ $permintaan->jenis_permintaan === 'tukar' ? 'Setujui Pindah Jadwal' : 'Setujui Sanggahan' }}
      </button>
      <button name="action" value="reject" type="submit" class="btn-outline">Tolak Permintaan</button>
    </div>
    <p class="note">Jika ditolak, tugas pengaju tetap berjalan seperti semula.</p>
  </form>
@endif
@endsection