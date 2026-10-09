@if($anggota)<p>Nomor anggota: {{ $anggota->nomor_anggota }}</p>@else<p>Nomor anggota dibuat otomatis saat disimpan.</p>@endif
<label>Nama lengkap <input name="nama_lengkap" value="{{ old('nama_lengkap', $anggota?->nama_lengkap) }}" required></label>
<label>Email <input type="email" name="email" value="{{ old('email', $anggota?->email) }}" required></label>
@unless($anggota)<label>Kata sandi <input type="password" name="password" required minlength="6"></label>@endunless
<label>Nomor WhatsApp <input name="nomor_wa" value="{{ old('nomor_wa', $anggota?->nomor_wa) }}"></label>
<label>Alamat <textarea name="alamat">{{ old('alamat', $anggota?->alamat) }}</textarea></label>
<label><input type="hidden" name="status_aktif" value="0"><input type="checkbox" name="status_aktif" value="1" @checked(old('status_aktif', $anggota?->status_aktif ?? true))> Aktif</label>
