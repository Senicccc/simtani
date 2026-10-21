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
      <label for="penugasan_id">1. Tugas yang ingin diubah</label>
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
      <label for="jenis_permintaan">2. Apa yang ingin dilakukan?</label>
      <select id="jenis_permintaan" name="jenis_permintaan" class="@error('jenis_permintaan') is-invalid @enderror" required>
        <option value="tukar" @selected(old('jenis_permintaan', 'tukar') === 'tukar')>Pindah ke jadwal lain</option>
        <option value="sanggah" @selected(old('jenis_permintaan') === 'sanggah')>Sanggah / tidak sanggup mengerjakan tugas</option>
      </select>
      @error('jenis_permintaan') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field" id="target-label">
      <label for="target_jadwal_id">3. Jadwal tujuan</label>
      <select id="target_jadwal_id" name="target_jadwal_id" class="@error('target_jadwal_id') is-invalid @enderror">
        <option value="">Tidak ada jadwal tersedia</option>
        @foreach ($availableTargets as $target)
          <option
            value="{{ $target['schedule']->id }}"
            data-assignment-id="{{ $target['assignment_id'] }}"
            @selected(old('target_jadwal_id') == $target['schedule']->id)
          >
            {{ $target['schedule']->nama_kegiatan }} · {{ $target['schedule']->tanggal?->format('d/m/Y') }} · tersedia
          </option>
        @endforeach
      </select>
      @error('target_jadwal_id')
        <span class="error">{{ $message }}</span>
      @else
        <span class="hint">Daftar hanya menampilkan jadwal yang masih memiliki tempat, sesuai kompetensi, dan tidak berbenturan.</span>
      @enderror
    </div>

    <div class="field">
      <label for="alasan">4. Alasan</label>
      <textarea id="alasan" name="alasan" required maxlength="2000" class="@error('alasan') is-invalid @enderror">{{ old('alasan') }}</textarea>
      @error('alasan') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-primary">Kirim Permintaan</button>
    </div>
  </form>

  <script>
    const requestType = document.getElementById('jenis_permintaan');
    const sourceSelect = document.querySelector('[name="penugasan_id"]');
    const targetSelect = document.getElementById('target_jadwal_id');
    const targetPlaceholder = targetSelect.options[0];
    const targetOptions = [...targetSelect.options].slice(1);
    const updateTargetChoices = () => {
      const isMove = requestType.value === 'tukar';
      const hasAvailableTargets = targetOptions.some((option) => option.dataset.assignmentId === sourceSelect.value);
      targetPlaceholder.textContent = isMove ? 'Tidak ada jadwal tersedia' : 'Tidak memilih jadwal';
      targetSelect.required = isMove && hasAvailableTargets;
      targetOptions.forEach((option) => {
        option.hidden = option.dataset.assignmentId !== sourceSelect.value;
        option.disabled = option.hidden;
      });
      if (targetSelect.selectedOptions[0]?.disabled) {
        targetSelect.value = '';
      }
      if (!isMove) {
        targetSelect.value = '';
      }
      targetPlaceholder.selected = !targetSelect.value;
    };
    requestType.addEventListener('change', updateTargetChoices);
    sourceSelect.addEventListener('change', updateTargetChoices);
    updateTargetChoices();
  </script>
@endif
@endsection