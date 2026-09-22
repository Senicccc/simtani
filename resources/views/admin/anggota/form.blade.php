@if($anggota)<label>Nomor anggota <input value="{{ $anggota->nomor_anggota }}" disabled></label>@else<p>Nomor anggota dibuat otomatis saat disimpan.</p>@endif
<label>Nama lengkap <input name="nama_lengkap" value="{{ old('nama_lengkap', $anggota?->nama_lengkap) }}" required></label>
<label>Email <input type="email" name="email" value="{{ old('email', $anggota?->email) }}" required></label>
<label>Password <input type="password" name="password" {{ $anggota ? '' : 'required' }}></label>
<label>Nomor WhatsApp <input name="nomor_wa" value="{{ old('nomor_wa', $anggota?->nomor_wa) }}"></label>
<label>Alamat <textarea name="alamat">{{ old('alamat', $anggota?->alamat) }}</textarea></label>
<label><input type="checkbox" name="status_aktif" value="1" @checked(old('status_aktif', $anggota?->status_aktif ?? true))> Aktif</label>
