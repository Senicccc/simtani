@extends('layouts.backend')

@section('title', 'Detail Permintaan')

@section('content')
<h2>Detail Permintaan Perubahan</h2>
<dl>
  <dt>Pengaju</dt><dd>{{ $permintaan->diajukanOleh->nama_lengkap }}</dd>
  <dt>Tugas pengaju</dt><dd><a href="{{ route('admin.penugasan.show', $permintaan->penugasan) }}">{{ $permintaan->penugasan->jadwal->nama_kegiatan }} · {{ $permintaan->penugasan->jadwal->tanggal?->format('d-m-Y') }}</a></dd>
  <dt>Jenis</dt><dd>{{ $permintaan->jenis_permintaan === 'tukar' ? 'Pindah ke jadwal lain' : 'Sanggah / tidak sanggup mengerjakan' }}</dd>
  @if($permintaan->targetJadwal)
    <dt>Jadwal tujuan yang diminta</dt><dd>{{ $permintaan->targetJadwal->nama_kegiatan }} · {{ $permintaan->targetJadwal->tanggal?->format('d-m-Y') }}</dd>
  @elseif($permintaan->targetPenugasan)
    <dt>Jadwal tujuan riwayat lama</dt><dd>{{ $permintaan->targetPenugasan->jadwal->nama_kegiatan }} · {{ $permintaan->targetPenugasan->jadwal->tanggal?->format('d-m-Y') }}</dd>
  @else
    <dt>Pengganti yang diminta</dt><dd>Tidak ada; jika disetujui, tugas akan dibatalkan tanpa pengganti.</dd>
  @endif
  <dt>Alasan</dt><dd>{{ $permintaan->alasan }}</dd>
  <dt>Status</dt><dd>{{ ['pending' => 'Menunggu diproses', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$permintaan->status] ?? $permintaan->status }}</dd>
  @if($permintaan->diprosesOleh)
    <dt>Diproses oleh</dt><dd>{{ $permintaan->diprosesOleh->nama_lengkap }} pada {{ $permintaan->diproses_at?->format('d-m-Y H:i') }}</dd>
  @endif
  @if($permintaan->catatan_admin)
    <dt>Catatan admin</dt><dd>{{ $permintaan->catatan_admin }}</dd>
  @endif
</dl>
@if($permintaan->status === 'pending')
  <form method="post" action="{{ route('admin.permintaan-perubahan.process', $permintaan) }}">
    @csrf
    @if($permintaan->jenis_permintaan === 'sanggah')
      <label>Jadwal pengganti jika permintaan disetujui (opsional)
        <select name="target_jadwal_id">
          <option value="">Tidak ada pengganti, batalkan tugas</option>
          @foreach($availableTargets as $target)
            <option value="{{ $target->id }}" @selected(old('target_jadwal_id', $permintaan->target_jadwal_id) == $target->id)>
              {{ $target->nama_kegiatan }} · {{ $target->tanggal?->format('d-m-Y') }} · tersedia
            </option>
          @endforeach
        </select>
      </label>
    @else
      <p>Jika disetujui, tugas pengaju dipindahkan ke jadwal tujuan yang dipilih anggota.</p>
    @endif
    <label>Catatan admin <textarea name="catatan_admin" maxlength="2000">{{ old('catatan_admin') }}</textarea></label>
    <button name="action" value="approve" type="submit" class="btn-primary">
      {{ $permintaan->jenis_permintaan === 'tukar' ? 'Setujui pindah jadwal' : 'Setujui sanggahan' }}
    </button>
    <button name="action" value="reject" type="submit" class="btn-outline">Tolak permintaan (tugas tetap)</button>
  </form>
@endif
@endsection
