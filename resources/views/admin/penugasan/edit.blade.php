@extends('layouts.backend')
@section('title', 'Edit Penugasan')

@section('content')
<div class="page-head">
  <div>
    <h2>Ubah Penugasan</h2>
    <p>{{ $penugasan->jadwal->nama_kegiatan }}</p>
  </div>
  <a href="{{ route('admin.penugasan.show', $penugasan) }}" class="btn-outline">Kembali ke detail tugas</a>
</div>

<form method="post" action="{{ route('admin.penugasan.update', $penugasan) }}" class="form-page panel">
  @csrf
  @method('PUT')

  <div class="field">
    <label for="anggota-search">Cari anggota</label>
    <input id="anggota-search" type="search" placeholder="Ketik nama atau nomor anggota" autocomplete="off">
  </div>

  <div class="field">
    <label for="anggota_id">Anggota yang ditugaskan</label>
    <select id="anggota_id" name="anggota_id" class="@error('anggota_id') is-invalid @enderror" required>
      @foreach ($anggota as $item)
        <option value="{{ $item->id }}" @selected(old('anggota_id', $penugasan->anggota_id) == $item->id)>{{ $item->nama_lengkap }} ({{ $item->nomor_anggota }})</option>
      @endforeach
    </select>
    @error('anggota_id') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <span class="field-label">Status tugas</span>
    @include('admin.partials.status-badge', ['type' => 'tugas', 'status' => $penugasan->status])
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Simpan Perubahan</button>
    <a href="{{ route('admin.penugasan.show', $penugasan) }}" class="btn-outline">Batal</a>
  </div>
</form>

<form method="post" action="{{ route('admin.penugasan.remove', $penugasan) }}" class="panel panel-body member-removal-form">
  @csrf
  <h3>Keluarkan Anggota dari Tugas</h3>
  <p class="note">Tugas dapat diteruskan tanpa pengganti jika masih ada minimal satu anggota yang bertugas. Pilih pengganti bila kebutuhan anggota harus tetap terpenuhi.</p>
  <div class="field">
    <label for="replacement_anggota_id">Anggota pengganti (opsional)</label>
    <select id="replacement_anggota_id" name="replacement_anggota_id">
      <option value="">Lanjut tanpa pengganti (minimal satu anggota tetap bertugas)</option>
      @foreach ($anggotaPengganti as $candidate)
        <option value="{{ $candidate->id }}">{{ $candidate->nama_lengkap }} ({{ $candidate->nomor_anggota }})</option>
      @endforeach
    </select>
    @error('replacement_anggota_id') <span class="error">{{ $message }}</span> @enderror
  </div>
  <button type="submit" class="btn-danger" onclick="return confirm('Keluarkan anggota ini dari tugas?')">Keluarkan Anggota</button>
</form>
@endsection

@push('scripts')
<script>
  const memberSearch = document.getElementById('anggota-search');
  const memberSelect = document.getElementById('anggota_id');
  memberSearch?.addEventListener('input', () => {
    const searchTerm = memberSearch.value.trim().toLocaleLowerCase();
    Array.from(memberSelect.options).forEach((option) => {
      option.hidden = option.value !== '{{ $penugasan->anggota_id }}' && !option.text.toLocaleLowerCase().includes(searchTerm);
    });
    if (memberSelect.selectedOptions[0]?.hidden) {
      memberSelect.value = '';
    }
  });
</script>
@endpush
