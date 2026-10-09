<label>Jenis pekerjaan <select name="jenis_pekerjaan_id" required>@foreach($jenisPekerjaan as $jenis)<option value="{{ $jenis->id }}" @selected(old('jenis_pekerjaan_id', $jadwal?->jenis_pekerjaan_id) == $jenis->id)>{{ $jenis->nama_pekerjaan }}</option>@endforeach</select></label>
<label>Nama kegiatan <input name="nama_kegiatan" value="{{ old('nama_kegiatan', $jadwal?->nama_kegiatan) }}" required></label>
<label>Deskripsi <textarea name="deskripsi">{{ old('deskripsi', $jadwal?->deskripsi) }}</textarea></label>
<label>Lokasi <input name="lokasi" value="{{ old('lokasi', $jadwal?->lokasi) }}" required></label>
<label>Tanggal <input type="date" name="tanggal" value="{{ old('tanggal', $jadwal?->tanggal?->format('Y-m-d')) }}" required></label>
<label>Tanggal selesai <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', $jadwal?->tanggal_selesai?->format('Y-m-d') ?? $jadwal?->tanggal?->format('Y-m-d')) }}" min="{{ old('tanggal', $jadwal?->tanggal?->format('Y-m-d')) }}" required></label>
<small>Semua hari dalam rentang ini dihitung sebagai hari kerja dan presensinya dicatat satu per satu.</small>
<label>Mulai <input type="time" name="waktu_mulai" value="{{ old('waktu_mulai', $jadwal?->waktu_mulai ? substr($jadwal->waktu_mulai, 0, 5) : null) }}" required></label>
<label>Selesai <input type="time" name="waktu_selesai" value="{{ old('waktu_selesai', $jadwal?->waktu_selesai ? substr($jadwal->waktu_selesai, 0, 5) : null) }}" required></label>
<label>Jumlah orang <input type="number" name="jumlah_orang_dibutuhkan" value="{{ old('jumlah_orang_dibutuhkan', $jadwal?->jumlah_orang_dibutuhkan) }}" min="1" required></label>
<label>Jumlah satuan <input type="number" step="0.01" name="jumlah_satuan" value="{{ old('jumlah_satuan', $jadwal?->jumlah_satuan ?? 1) }}" min="0.01" required></label><small>Jumlah hasil kerja yang menjadi dasar upah, sesuai satuan jenis pekerjaan, misalnya 3 petak atau 10 ekor.</small>
<label>Total upah kegiatan (Rp) <input type="number" name="total_upah" value="{{ old('total_upah', $jadwal?->total_upah) }}" min="0.01" step="0.01" required></label>
<small>Total ini dibagi rata kepada anggota yang menyelesaikan tugas setelah semua laporan selesai diperiksa.</small>
<label>Verifikasi penyelesaian
  <select name="perlu_verifikasi" required>
    <option value="1" @selected((string) old('perlu_verifikasi', $jadwal?->perlu_verifikasi ?? true) === '1')>Admin memeriksa sebelum tugas selesai</option>
    <option value="0" @selected((string) old('perlu_verifikasi', $jadwal?->perlu_verifikasi ?? true) === '0')>Tugas langsung selesai saat anggota melapor</option>
  </select>
</label>
@if($jadwal)<label>Metode penugasan <select name="metode_penjadwalan"><option value="manual">Manual</option><option value="otomatis" @selected($jadwal->metode_penjadwalan === 'otomatis')>Otomatis</option></select></label><label>Status <select name="status"><option value="scheduled">Terjadwal</option><option value="cancelled" @selected($jadwal->status === 'cancelled')>Dibatalkan</option></select></label>@endif
