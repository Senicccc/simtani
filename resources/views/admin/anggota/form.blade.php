<div class="form-section">
  <h3>Data Diri</h3>

  <div class="field">
    <label for="nama_lengkap">Nama lengkap</label>
    <input id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $anggota?->nama_lengkap) }}"
           autocomplete="name" class="@error('nama_lengkap') is-invalid @enderror" required>
    @error('nama_lengkap') <span class="error">{{ $message }}</span> @enderror
  </div>

  <div class="form-row">
    <div class="field">
      <label for="nomor_wa">Nomor WhatsApp</label>
      <input id="nomor_wa" name="nomor_wa" value="{{ old('nomor_wa', $anggota?->nomor_wa) }}"
             inputmode="tel" autocomplete="tel" placeholder="08123456789"
             class="@error('nomor_wa') is-invalid @enderror">
      @error('nomor_wa') <span class="error">{{ $message }}</span> @enderror
    </div>
  </div>

  <div class="field">
    <label for="alamat">Alamat</label>
    <textarea id="alamat" name="alamat" class="@error('alamat') is-invalid @enderror">{{ old('alamat', $anggota?->alamat) }}</textarea>
    @error('alamat') <span class="error">{{ $message }}</span> @enderror
  </div>
</div>

<div class="form-section">
  <h3>Akun Masuk</h3>
  <div class="form-row">
    <div class="field">
      <label for="email">Email</label>
      <input id="email" type="email" name="email" value="{{ old('email', $anggota?->email) }}"
             autocomplete="off" class="@error('email') is-invalid @enderror" required>
      @error('email') <span class="error">{{ $message }}</span> @enderror
    </div>

    @unless ($anggota)
      <div class="field">
        <label for="password">Kata sandi</label>
        <input id="password" type="password" name="password" autocomplete="new-password"
               minlength="6" class="@error('password') is-invalid @enderror" required>
        @error('password')
          <span class="error">{{ $message }}</span>
        @else
          <span class="hint">Minimal 6 karakter.</span>
        @enderror
      </div>
    @endunless
  </div>
</div>

<div class="form-section">
  <h3>Status</h3>
  <input type="hidden" name="status_aktif" value="0">
  <label class="check-row">
    <input type="checkbox" name="status_aktif" value="1"
           @checked(old('status_aktif', $anggota?->status_aktif ?? true))>
    <span>Anggota aktif</span>
  </label>
</div>