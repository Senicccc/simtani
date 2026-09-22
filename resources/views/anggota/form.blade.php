@if ($anggota)
  <div class="field">
    <label for="nomor_anggota">Nomor anggota</label>
    <input id="nomor_anggota" name="nomor_anggota" value="{{ $anggota->nomor_anggota }}" readonly>
  </div>
@else
  <p style="font-size:12px;color:var(--text-muted);margin:-6px 0 14px">Nomor anggota dibuat otomatis saat disimpan.</p>
@endif

<div class="field">
  <label for="nama_lengkap">Nama lengkap</label>
  <input id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $anggota?->nama_lengkap) }}" required>
</div>

<div class="field">
  <label for="email">Email</label>
  <input id="email" name="email" type="email" value="{{ old('email', $anggota?->email) }}" required>
</div>

<div class="field">
  <label for="password">Password</label>
  <input id="password" name="password" type="password" placeholder="{{ $anggota ? 'Kosongkan jika tidak diubah' : '' }}" {{ $anggota ? '' : 'required' }}>
</div>

<div class="field">
  <label for="nomor_wa">Nomor WhatsApp</label>
  <input id="nomor_wa" name="nomor_wa" value="{{ old('nomor_wa', $anggota?->nomor_wa) }}">
</div>

<div class="field">
  <label for="alamat">Alamat</label>
  <textarea id="alamat" name="alamat">{{ old('alamat', $anggota?->alamat) }}</textarea>
</div>

<div class="field" style="display:flex;align-items:center;gap:8px">
  <input type="checkbox" id="status_aktif" name="status_aktif" value="1" style="width:auto" @checked(old('status_aktif', $anggota?->status_aktif ?? true))>
  <label for="status_aktif" style="margin:0">Aktif</label>
</div>
