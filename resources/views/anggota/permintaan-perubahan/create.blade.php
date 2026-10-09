@extends('layouts.backend')

@section('title', 'Buat Permintaan Perubahan')

@section('content')
<h2>Ajukan Perubahan Tugas</h2>
<p>Ajukan perubahan untuk tugas yang belum dimulai. Perubahan berlaku setelah disetujui admin.</p>
@if($penugasan->isEmpty())
  <p>Tidak ada tugas yang bisa diajukan sekarang. Tugas yang sudah dimulai tidak bisa dipindahkan. <a href="{{ route('anggota.tugas.index') }}">Kembali ke daftar tugas</a></p>
@else
  <form method="post" action="{{ route('anggota.permintaan-perubahan.store') }}" class="form-page" style="max-width:720px">
    @csrf
    <label>1. Tugas yang ingin diubah
      <select name="penugasan_id" required>
        <option value="">Pilih tugas</option>
        @foreach($penugasan as $item)
          <option value="{{ $item->id }}" @selected(old('penugasan_id', request('penugasan_id')) == $item->id)>
            {{ $item->jadwal->nama_kegiatan }} · {{ $item->jadwal->tanggal?->format('d-m-Y') }}
          </option>
        @endforeach
      </select>
    </label>
    <label>2. Apa yang ingin dilakukan?
      <select id="jenis_permintaan" name="jenis_permintaan" required>
        <option value="tukar" @selected(old('jenis_permintaan', 'tukar') === 'tukar')>Pindah ke jadwal lain</option>
        <option value="sanggah" @selected(old('jenis_permintaan') === 'sanggah')>Sanggah / tidak sanggup mengerjakan tugas</option>
      </select>
    </label>
    <label id="target-label">3. Jadwal tujuan
      <select id="target_jadwal_id" name="target_jadwal_id">
        <option value="">Tidak memilih jadwal pengganti</option>
        @foreach($availableTargets as $target)
          <option
            value="{{ $target['schedule']->id }}"
            data-assignment-id="{{ $target['assignment_id'] }}"
            @selected(old('target_jadwal_id') == $target['schedule']->id)
          >
            {{ $target['schedule']->nama_kegiatan }} · {{ $target['schedule']->tanggal?->format('d-m-Y') }} · tersedia
          </option>
        @endforeach
      </select>
      <small>Daftar hanya menampilkan jadwal yang masih memiliki tempat, sesuai kompetensi, dan tidak berbenturan.</small>
    </label>
    <label>4. Alasan
      <textarea name="alasan" required maxlength="2000">{{ old('alasan') }}</textarea>
    </label>
    <button type="submit" class="btn-primary">Kirim permintaan</button>
  </form>
  <script>
    const requestType = document.getElementById('jenis_permintaan');
    const sourceSelect = document.querySelector('[name="penugasan_id"]');
    const targetSelect = document.getElementById('target_jadwal_id');
    const targetOptions = [...targetSelect.options].slice(1);
    const updateTargetChoices = () => {
      const isMove = requestType.value === 'tukar';
      targetSelect.required = isMove;
      targetOptions.forEach((option) => {
        option.hidden = option.dataset.assignmentId !== sourceSelect.value;
        option.disabled = option.hidden;
      });
      if (targetSelect.selectedOptions[0]?.disabled) {
        targetSelect.value = '';
      }
    };
    requestType.addEventListener('change', updateTargetChoices);
    sourceSelect.addEventListener('change', updateTargetChoices);
    updateTargetChoices();
  </script>
@endif
@endsection
