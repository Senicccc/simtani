<div class="form-section">
  <h3>Kegiatan</h3>

  <div class="field">
    <label for="jenis_pekerjaan_id">Jenis pekerjaan</label>
    <select id="jenis_pekerjaan_id" name="jenis_pekerjaan_id" class="@error('jenis_pekerjaan_id') is-invalid @enderror" required>
      @foreach ($jenisPekerjaan as $jenis)
        <option value="{{ $jenis->id }}" @selected(old('jenis_pekerjaan_id', $jadwal?->jenis_pekerjaan_id) == $jenis->id)>{{ $jenis->nama_pekerjaan }}</option>
      @endforeach
    </select>
    @error('jenis_pekerjaan_id') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="nama_kegiatan">Nama kegiatan</label>
    <input id="nama_kegiatan" name="nama_kegiatan" value="{{ old('nama_kegiatan', $jadwal?->nama_kegiatan) }}"
           class="@error('nama_kegiatan') is-invalid @enderror" required>
    @error('nama_kegiatan') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="deskripsi">Deskripsi</label>
    <textarea id="deskripsi" name="deskripsi" class="@error('deskripsi') is-invalid @enderror">{{ old('deskripsi', $jadwal?->deskripsi) }}</textarea>
    @error('deskripsi') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="field">
    <label for="lokasi">Lokasi</label>
    <input id="lokasi" name="lokasi" value="{{ old('lokasi', $jadwal?->lokasi) }}"
           class="@error('lokasi') is-invalid @enderror" required>
    @error('lokasi') <span class="error">{{ $message }}</span> @enderror
  </div>
</div>

<div class="form-section">
  <h3>Waktu</h3>

  <div class="form-row">
    <div class="field">
      <label for="tanggal">Tanggal</label>
      <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', $jadwal?->tanggal?->format('Y-m-d')) }}"
             class="@error('tanggal') is-invalid @enderror" required>
      @error('tanggal') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="field">
      <label for="tanggal_selesai">Tanggal selesai</label>
      <input id="tanggal_selesai" type="date" name="tanggal_selesai"
             value="{{ old('tanggal_selesai', $jadwal?->tanggal_selesai?->format('Y-m-d') ?? $jadwal?->tanggal?->format('Y-m-d')) }}"
             min="{{ old('tanggal', $jadwal?->tanggal?->format('Y-m-d')) }}"
             class="@error('tanggal_selesai') is-invalid @enderror" required>
      @error('tanggal_selesai') <span class="error">{{ $message }}</span> @enderror
    </div>
  </div>
  <p class="form-note">Semua hari dalam rentang ini dihitung sebagai hari kerja dan presensinya dicatat satu per satu.</p>

  <div class="form-row">
    <div class="field">
      <label for="waktu_mulai">Jam mulai</label>
      <input id="waktu_mulai" type="time" name="waktu_mulai"
             value="{{ old('waktu_mulai', $jadwal?->waktu_mulai ? substr($jadwal->waktu_mulai, 0, 5) : null) }}"
             class="@error('waktu_mulai') is-invalid @enderror" required>
      @error('waktu_mulai') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="field">
      <label for="waktu_selesai">Jam selesai</label>
      <input id="waktu_selesai" type="time" name="waktu_selesai"
             value="{{ old('waktu_selesai', $jadwal?->waktu_selesai ? substr($jadwal->waktu_selesai, 0, 5) : null) }}"
             class="@error('waktu_selesai') is-invalid @enderror" required>
      @error('waktu_selesai') <span class="error">{{ $message }}</span> @enderror
    </div>
  </div>
</div>

<div class="form-section">
  <h3>Kebutuhan dan Upah</h3>

  <div class="form-row">
    <div class="field">
      <label for="jumlah_orang_dibutuhkan">Jumlah orang</label>
      <input id="jumlah_orang_dibutuhkan" type="number" name="jumlah_orang_dibutuhkan" min="1"
             value="{{ old('jumlah_orang_dibutuhkan', $jadwal?->jumlah_orang_dibutuhkan) }}"
             class="@error('jumlah_orang_dibutuhkan') is-invalid @enderror" required>
      @error('jumlah_orang_dibutuhkan') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="field">
      <label for="jumlah_satuan">Jumlah satuan</label>
      <input id="jumlah_satuan" type="number" step="0.01" min="0.01" name="jumlah_satuan"
             value="{{ old('jumlah_satuan', $jadwal?->jumlah_satuan ?? 1) }}"
             class="@error('jumlah_satuan') is-invalid @enderror" required>
      @error('jumlah_satuan') <span class="error">{{ $message }}</span> @enderror
    </div>
  </div>
  <p class="form-note">Jumlah satuan adalah hasil kerja yang menjadi dasar upah, sesuai satuan jenis pekerjaan, misalnya 3 petak atau 10 ekor.</p>

  <div class="field">
    <label for="total_upah">Total upah kegiatan (Rp)</label>
    <input id="total_upah" type="number" step="0.01" min="0.01" name="total_upah"
           value="{{ old('total_upah', $jadwal?->total_upah) }}"
           class="@error('total_upah') is-invalid @enderror" required>
    @error('total_upah')
      <span class="error">{{ $message }}</span>
    @else
      <span class="hint">Total ini dibagi rata kepada anggota yang menyelesaikan tugas setelah semua laporan selesai diperiksa.</span>
    @enderror
  </div>
</div>

<div class="form-section">
  <h3>Penyelesaian Tugas</h3>
  <div class="field">
    <label for="perlu_verifikasi">Verifikasi penyelesaian</label>
    <select id="perlu_verifikasi" name="perlu_verifikasi" class="@error('perlu_verifikasi') is-invalid @enderror" required>
      <option value="1" @selected((string) old('perlu_verifikasi', $jadwal?->perlu_verifikasi ?? true) === '1')>Admin memeriksa sebelum tugas selesai</option>
      <option value="0" @selected((string) old('perlu_verifikasi', $jadwal?->perlu_verifikasi ?? true) === '0')>Tugas langsung selesai saat anggota melapor</option>
    </select>
    @error('perlu_verifikasi') <span class="error">{{ $message }}</span> @enderror
  </div>
</div>

@if ($jadwal)
  <div class="form-section">
    <h3>Pengaturan</h3>
    <div class="form-row">
      <div class="field">
        <label for="metode_penjadwalan">Metode penugasan</label>
        <select id="metode_penjadwalan" name="metode_penjadwalan" class="@error('metode_penjadwalan') is-invalid @enderror">
          <option value="manual">Manual</option>
          <option value="otomatis" @selected(old('metode_penjadwalan', $jadwal->metode_penjadwalan) === 'otomatis')>Otomatis</option>
        </select>
        @error('metode_penjadwalan') <span class="error">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" class="@error('status') is-invalid @enderror">
          <option value="scheduled">Terjadwal</option>
          <option value="cancelled" @selected(old('status', $jadwal->status) === 'cancelled')>Dibatalkan</option>
        </select>
        @error('status') <span class="error">{{ $message }}</span> @enderror
      </div>
    </div>
  </div>
@endif