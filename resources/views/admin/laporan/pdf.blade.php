<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Laporan SIMTANI</title>
  <style>
    body{font-family:Arial,sans-serif;font-size:12px;color:#111}
    h1,h2{margin:0 0 12px}
    h2{margin-top:24px}
    table{border-collapse:collapse;width:100%;margin:12px 0 20px}
    th,td{border:1px solid #999;padding:6px;text-align:left}
    button{margin-bottom:16px}
    @media print{button{display:none}}
  </style>
</head>
<body>
  <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
  <h1>Laporan SIMTANI</h1>
  <p>Filter: {{ $q ?: 'Semua nama' }} | {{ $start ?: 'Awal' }} s/d {{ $end ?: 'Akhir' }}</p>

  <h2>Jadwal kegiatan</h2>
  <table><thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Jenis pekerjaan</th><th>Lokasi</th><th>Status</th></tr></thead><tbody>
    @foreach($rekapJadwal as $item)
      <tr><td>{{ $item->tanggal?->format('d-m-Y') }}</td><td>{{ $item->nama_kegiatan }}</td><td>{{ $item->jenisPekerjaan->nama_pekerjaan ?? '-' }}</td><td>{{ $item->lokasi }}</td><td>{{ ['draft' => 'Draf', 'scheduled' => 'Terjadwal', 'ongoing' => 'Sedang berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td></tr>
    @endforeach
  </tbody></table>

  <h2>Penugasan</h2>
  <table><thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Anggota</th><th>Status tugas</th><th>Presensi</th><th>Upah</th></tr></thead><tbody>
    @foreach($rekapTugas as $item)
      <tr><td>{{ $item->jadwal->tanggal?->format('d-m-Y') }}</td><td>{{ $item->jadwal->nama_kegiatan }}</td><td>{{ $item->anggota->nama_lengkap }}</td><td>{{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td><td>{{ ['hadir' => 'Hadir', 'izin' => 'Izin', 'tidak_hadir' => 'Tidak hadir'][$item->presensi?->status] ?? 'Belum dicatat' }}</td><td>{{ $item->upah ? 'Rp '.number_format((float) $item->upah->jumlah_upah, 0, ',', '.') : 'Belum dihitung' }}</td></tr>
    @endforeach
  </tbody></table>

  <h2>Presensi</h2>
  <table><thead><tr><th>Kegiatan</th><th>Anggota</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Catatan</th></tr></thead><tbody>
    @foreach($rekapPresensi as $item)
      <tr><td>{{ $item->penugasan->jadwal->nama_kegiatan }}</td><td>{{ $item->penugasan->anggota->nama_lengkap }}</td><td>{{ $item->waktu_check_in?->format('d-m-Y H:i') ?? '-' }}</td><td>{{ $item->waktu_check_out?->format('d-m-Y H:i') ?? '-' }}</td><td>{{ ['hadir' => 'Hadir', 'izin' => 'Izin', 'tidak_hadir' => 'Tidak hadir'][$item->status] ?? $item->status }}</td><td>{{ $item->catatan }}</td></tr>
    @endforeach
  </tbody></table>

  <h2>Upah</h2>
  <table><thead><tr><th>Kegiatan</th><th>Anggota</th><th>Jumlah</th><th>Status pembayaran</th><th>Metode</th><th>Tanggal bayar</th></tr></thead><tbody>
    @foreach($rekapUpah as $item)
      <tr><td>{{ $item->penugasan->jadwal->nama_kegiatan }}</td><td>{{ $item->penugasan->anggota->nama_lengkap }}</td><td>Rp {{ number_format((float) $item->jumlah_upah, 0, ',', '.') }}</td><td>{{ $item->status_pembayaran === 'sudah_dibayar' ? 'Sudah dibayar' : 'Belum dibayar' }}</td><td>{{ $item->metode_pembayaran ?? '-' }}</td><td>{{ $item->waktu_pembayaran?->format('d-m-Y H:i') ?? '-' }}</td></tr>
    @endforeach
  </tbody></table>
</body>
</html>
