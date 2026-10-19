<div class="form-row">
  <div class="field">
    <label for="nama_pekerjaan">Nama pekerjaan</label>
    <input id="nama_pekerjaan" name="nama_pekerjaan"
           value="{{ old('nama_pekerjaan', $jenisPekerjaan->nama_pekerjaan ?? '') }}"
           class="@error('nama_pekerjaan') is-invalid @enderror" required>
    @error('nama_pekerjaan') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="satuan">Satuan</label>
    <input id="satuan" name="satuan"
           value="{{ old('satuan', $jenisPekerjaan->satuan ?? '') }}"
           class="@error('satuan') is-invalid @enderror" required>
    @error('satuan')
      <span class="error">{{ $message }}</span>
    @else
      <span class="hint">Contoh: karung, blok, petak.</span>
    @enderror
  </div>
</div>

<div class="field">
  <label for="deskripsi">Deskripsi</label>
  <textarea id="deskripsi" name="deskripsi"
            class="@error('deskripsi') is-invalid @enderror">{{ old('deskripsi', $jenisPekerjaan->deskripsi ?? '') }}</textarea>
  @error('deskripsi') <span class="error">{{ $message }}</span> @enderror
</div>