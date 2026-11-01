@extends('layouts.backend')
@section('title', 'Selesaikan Tugas')

@section('content')
<div class="page-head">
  <div>
    <h2>Laporkan Tugas Selesai</h2>
    <p class="meta-row">
      <span>{{ $penugasan->jadwal->nama_kegiatan }}</span>
      <span>{{ $penugasan->tanggalMulaiEfektif()->format('d/m/Y') }} – {{ $penugasan->tanggalSelesaiEfektif()->format('d/m/Y') }}</span>
    </p>
  </div>
  <a href="{{ route('anggota.tugas.show', $penugasan) }}" class="btn-outline">Kembali ke detail tugas</a>
</div>

<form method="post" action="{{ route('anggota.tugas.complete', $penugasan) }}" enctype="multipart/form-data" class="form-page panel"
      onsubmit="return confirm({{ \Illuminate\Support\Js::from($penugasan->jadwal->perlu_verifikasi ? 'Kirim laporan ini untuk diperiksa admin?' : 'Selesaikan tugas ini sekarang?') }})">
  @csrf

  @if($penugasan->jadwal->perlu_verifikasi)
    <p class="note">Laporan akan diperiksa admin. Total upah kegiatan Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }} dibagi rata setelah semua tugas selesai dan disetujui.</p>
  @else
    <p class="note">Tugas selesai otomatis setelah laporan dikirim. Total upah kegiatan Rp {{ number_format((float) $penugasan->jadwal->total_upah, 0, ',', '.') }} dibagi rata setelah semua anggota selesai.</p>
  @endif

  <div class="field">
    <label for="tanggal_selesai">Tanggal selesai</label>
    <input id="tanggal_selesai" type="date" name="tanggal_selesai"
           value="{{ old('tanggal_selesai', today()->toDateString()) }}"
           min="{{ $penugasan->tanggalMulaiEfektif()->toDateString() }}"
           max="{{ min(today()->toDateString(), $penugasan->tanggalSelesaiEfektif()->toDateString()) }}"
           class="@error('tanggal_selesai') is-invalid @enderror" required>
    @error('tanggal_selesai') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="catatan_penyelesaian">Catatan (boleh dikosongkan)</label>
    <textarea id="catatan_penyelesaian" name="catatan_penyelesaian" class="@error('catatan_penyelesaian') is-invalid @enderror">{{ old('catatan_penyelesaian') }}</textarea>
    @error('catatan_penyelesaian') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="bukti_selesai">Bukti foto atau PDF (boleh dikosongkan)</label>
    <input id="bukti_selesai" type="file" name="bukti_selesai"
           accept="image/jpeg,image/png,image/webp,application/pdf" data-maks-mb="5"
           class="@error('bukti_selesai') is-invalid @enderror">
    @error('bukti_selesai')
      <span class="error">{{ $message }}</span>
    @else
      <span class="hint">JPG, PNG, WEBP, atau PDF, maksimal 5 MB.</span>
    @enderror
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">{{ $penugasan->jadwal->perlu_verifikasi ? 'Kirim untuk Diperiksa' : 'Laporkan dan Selesaikan Tugas' }}</button>
  </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
  const input = document.getElementById('bukti_selesai');
  if (!input) return;
  input.addEventListener('change', function () {
    const file = input.files[0];
    const maks = Number(input.dataset.maksMb) * 1024 * 1024;
    if (file && file.size > maks) {
      alert('Ukuran file maksimal ' + input.dataset.maksMb + ' MB. Pilih file yang lebih kecil.');
      input.value = '';
    }
  });
})();
</script>
@endpush