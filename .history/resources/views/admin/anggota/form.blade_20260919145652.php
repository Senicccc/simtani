<label>Nomor anggota <input name="nomor_anggota" value="{{ old('nomor_anggota', $anggota?->nomor_anggota) }}" required></label>
<label>Nama lengkap <input name="nama_lengkap" value="{{ old('nama_lengkap', $anggota?->nama_lengkap) }}" required></label>
<label>Email <input type="email" name="email" value="{{ old('email', $anggota?->email) }}" required></label>
<label>Password <input type="password" name="password" {{ $anggota ? '' : 'required' }}></label>
<label>Nomor WhatsApp <input name="nomor_wa" value="{{ old('nomor_wa', $anggota?->nomor_wa) }}"></label>
<label>Alamat <textarea name="alamat">{{ old('alamat', $anggota?->alamat) }}</textarea></label>
<label><input type="checkbox" name="status_aktif" value="1" @checked(old('status_aktif', $anggota?->status_aktif ?? true))> Aktif</label>
<h3>Kompetensi</h3>
@foreach($jenisPekerjaan as $jenis)
<label><input type="checkbox" name="kompetensi[{{ $loop->index }}][jenis_pekerjaan_id]" value="{{ $jenis->id }}"> {{ $jenis->nama_pekerjaan }}</label>
<select name="kompetensi[{{ $loop->index }}][tingkat_kemampuan]"><option value="pemula">Pemula</option><option value="menengah">Menengah</option><option value="mahir">Mahir</option></select>
@endforeach
