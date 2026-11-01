@extends('layouts.backend')

@section('title', 'Buat Permintaan Perubahan')

@section('content')
<div class="page-head">
  <div>
    <h2>Ajukan Perubahan Tugas</h2>
    <p>Ajukan perubahan untuk tugas yang belum dimulai. Perubahan berlaku setelah disetujui admin.</p>
  </div>
</div>

@if ($penugasan->isEmpty())
  <div class="panel panel-body">
    <p class="note">Tidak ada tugas yang bisa diajukan sekarang. Tugas yang sudah dimulai tidak bisa dipindahkan.</p>
    <a href="{{ route('anggota.tugas.index') }}" class="btn-outline">Kembali ke daftar tugas</a>
  </div>
@else
  <form method="post" action="{{ route('anggota.permintaan-perubahan.store') }}" class="form-page panel">
    @csrf

    <div class="field">
      <label for="penugasan_id">Tugas yang ingin diubah</label>
      <select id="penugasan_id" name="penugasan_id" class="@error('penugasan_id') is-invalid @enderror" required>
        <option value="">Pilih tugas</option>
        @foreach ($penugasan as $item)
          <option value="{{ $item->id }}" @selected(old('penugasan_id', request('penugasan_id')) == $item->id)>
            {{ $item->jadwal->nama_kegiatan }} · {{ $item->jadwal->tanggal?->format('d/m/Y') }}
          </option>
        @endforeach
      </select>
      @error('penugasan_id') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
      <label for="jenis_permintaan">Apa yang ingin dilakukan?</label>
      <select id="jenis_permintaan" name="jenis_permintaan" class="@error('jenis_permintaan') is-invalid @enderror" required>
        <option value="tukar" @selected(old('jenis_permintaan', 'tukar') === 'tukar')>Pindah ke jadwal lain</option>
        <option value="sanggah" @selected(old('jenis_permintaan') === 'sanggah')>Sanggah / tidak sanggup mengerjakan tugas</option>
      </select>
      @error('jenis_permintaan') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field" id="target-label">
      <label for="target_jadwal_id">Jadwal tujuan</label>
      <select id="target_jadwal_id" name="target_jadwal_id" class="@error('target_jadwal_id') is-invalid @enderror">
        <option value="">Pilih tugas terlebih dahulu</option>
        @foreach ($availableTargets as $target)
          @php
            $jd = $target['schedule'];
            $rentang = $jd->tanggal?->format('d/m/Y');
            if ($jd->tanggal && $jd->tanggal_selesai && ! $jd->tanggal_selesai->isSameDay($jd->tanggal)) {
              $rentang .= ' – '.$jd->tanggal_selesai->format('d/m/Y');
            }
          @endphp
          <option value="{{ $jd->id }}" data-assignment-id="{{ $target['assignment_id'] }}" @selected(old('target_jadwal_id') == $jd->id)>
            {{ $jd->nama_kegiatan }} · {{ $rentang }}
          </option>
        @endforeach
      </select>
      @error('target_jadwal_id')
        <span class="error">{{ $message }}</span>
      @else
        <span class="hint">Daftar hanya menampilkan jadwal yang masih memiliki tempat, sesuai kompetensi, dan tidak berbenturan.</span>
      @enderror
      <span class="hint" id="target-info" aria-live="polite"></span>
    </div>

    <div class="field">
      <label for="alasan">Alasan</label>
      <textarea id="alasan" name="alasan" required maxlength="2000" class="@error('alasan') is-invalid @enderror">{{ old('alasan') }}</textarea>
      @error('alasan') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-primary">Kirim Permintaan</button>
    </div>
  </form>
@endif
@endsection

@if (! $penugasan->isEmpty())
@push('scripts')
<script>
(function () {
  const jenis = document.getElementById('jenis_permintaan');
  const sumber = document.getElementById('penugasan_id');
  const target = document.getElementById('target_jadwal_id');
  const bidang = document.getElementById('target-label');
  const info = document.getElementById('target-info');
  if (!jenis || !sumber || !target || !bidang) return;

  const semua = Array.from(target.options).slice(1).map(function (o) {
    return { value: o.value, text: o.text, tugas: o.dataset.assignmentId };
  });
  let dipilih = target.value;

  function perbarui() {
    const tukar = jenis.value === 'tukar';
    bidang.hidden = !tukar;
    if (target.value) dipilih = target.value;

    const cocok = semua.filter(function (o) { return o.tugas === sumber.value; });
    const kosong = !sumber.value ? 'Pilih tugas terlebih dahulu'
      : cocok.length === 0 ? 'Tidak ada jadwal tersedia'
      : 'Pilih jadwal tujuan';

    target.innerHTML = '';
    target.add(new Option(kosong, ''));
    cocok.forEach(function (o) {
      const opt = new Option(o.text, o.value);
      opt.dataset.assignmentId = o.tugas;
      opt.selected = tukar && o.value === dipilih;
      target.add(opt);
    });

    target.required = tukar && cocok.length > 0;
    info.textContent = (tukar && sumber.value && cocok.length === 0)
      ? 'Tidak ada jadwal tujuan untuk tugas ini. Pilih "Sanggah" jika Anda tidak sanggup mengerjakannya.'
      : '';
  }

  jenis.addEventListener('change', perbarui);
  sumber.addEventListener('change', perbarui);
  perbarui();
})();
</script>
@endpush
@endif