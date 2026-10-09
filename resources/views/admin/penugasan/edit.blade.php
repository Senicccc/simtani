@extends('layouts.backend')
@section('title', 'Edit Penugasan')
@section('content')
<h2>Ubah Penugasan</h2>
<p>{{ $penugasan->jadwal->nama_kegiatan }}</p>
<form method="post" action="{{ route('admin.penugasan.update', $penugasan) }}" class="form-page">
  @csrf
  @method('PUT')
  <label>Anggota
    <select name="anggota_id" required>
      @foreach($anggota as $item)
        <option value="{{ $item->id }}" @selected($penugasan->anggota_id === $item->id)>{{ $item->nama_lengkap }} ({{ $item->nomor_anggota }})</option>
      @endforeach
    </select>
  </label>
  <p>Status tugas: {{ $penugasan->status === 'assigned' ? 'Ditugaskan' : 'Sedang dikerjakan' }}.</p>
  <button type="submit">Simpan perubahan</button>
</form>
@endsection
