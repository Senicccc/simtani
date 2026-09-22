<label>Jenis pekerjaan <select name="jenis_pekerjaan_id" required>@foreach($jenisPekerjaan as $jenis)<option value="{{ $jenis->id }}" @selected(old('jenis_pekerjaan_id', $jadwal?->jenis_pekerjaan_id) == $jenis->id)>{{ $jenis->nama_pekerjaan }}</option>@endforeach</select></label>
<label>Nama kegiatan <input name="nama_kegiatan" value="{{ old('nama_kegiatan', $jadwal?->nama_kegiatan) }}" required></label>
<label>Deskripsi <textarea name="deskripsi">{{ old('deskripsi', $jadwal?->deskripsi) }}</textarea></label>
<label>Lokasi <input name="lokasi" value="{{ old('lokasi', $jadwal?->lokasi) }}" required></label>
<label>Tanggal <input type="date" name="tanggal" value="{{ old('tanggal', $jadwal?->tanggal?->format('Y-m-d')) }}" required></label>
<label>Mulai <input type="time" name="waktu_mulai" value="{{ old('waktu_mulai', $jadwal?->waktu_mulai) }}" required></label>
<label>Selesai <input type="time" name="waktu_selesai" value="{{ old('waktu_selesai', $jadwal?->waktu_selesai) }}" required></label>
<label>Jumlah orang <input type="number" name="jumlah_orang_dibutuhkan" value="{{ old('jumlah_orang_dibutuhkan', $jadwal?->jumlah_orang_dibutuhkan) }}" min="1" required></label>
<label>Jumlah satuan <input type="number" step="0.01" name="jumlah_satuan" value="{{ old('jumlah_satuan', $jadwal?->jumlah_satuan ?? 1) }}" min="0.01" required></label>
@if($jadwal)<label>Metode <select name="metode_penjadwalan"><option value="manual">Manual</option><option value="otomatis" @selected($jadwal->metode_penjadwalan === 'otomatis')>Otomatis</option></select></label><label>Status <select name="status"><option value="scheduled">Scheduled</option><option value="completed" @selected($jadwal->status === 'completed')>Completed</option><option value="cancelled" @selected($jadwal->status === 'cancelled')>Cancelled</option></select></label>@endif
