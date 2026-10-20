<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Laporan SIMTANI</title>
  <style>
    @page{size:A4 landscape;margin:14mm}
    *{box-sizing:border-box}
    body{margin:0;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.45;color:#1f2b22;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .toolbar{margin-bottom:16px}
    .toolbar button{padding:8px 14px;border:0;border-radius:6px;background:#3f7d4f;color:#fff;font:inherit;font-size:12px;cursor:pointer}
    header{margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #1f4d2c}
    h1{margin:0 0 4px;font-size:20px;color:#1f4d2c}
    .meta{margin:0;color:#556b5d}
    .meta span+span{margin-left:18px}
    .summary{display:flex;gap:10px;margin:0 0 4px}
    .summary div{flex:1;padding:8px 10px;border:1px solid #a9c9b0;background:#e8f2ea}
    .summary b{display:block;margin-top:2px;font-size:14px;color:#1f4d2c}
    h2{margin:18px 0 6px;font-size:13px;color:#1f4d2c;break-after:avoid}
    table{width:100%;border-collapse:collapse;margin:0 0 6px}
    thead{display:table-header-group}
    tr{break-inside:avoid}
    th,td{padding:5px 7px;border:1px solid #a9c9b0;text-align:left;vertical-align:top}
    th{background:#e8f2ea;color:#1f4d2c;font-weight:700}
    .num{text-align:right;white-space:nowrap}
    .empty{color:#6f8577;text-align:center}
    .foot{margin-top:16px;color:#6f8577;font-size:10px}
    @media print{.toolbar{display:none}}
    @media screen{body{max-width:1100px;margin:0 auto;padding:24px}}
  </style>
</head>
<body>
  <div class="toolbar"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>

  <header>
    <h1>Laporan SIMTANI</h1>
    <p class="meta">
      <span>Periode: {{ $start ?: 'Awal' }} s/d {{ $end ?: 'Akhir' }}</span>
      <span>Filter nama: {{ $q ?: 'Semua nama' }}</span>
    </p>
  </header>

  <div class="summary">
    <div>Jadwal ditemukan<b>{{ $rekapJadwal->count() }}</b></div>
    <div>Penugasan selesai<b>{{ $rekapTugas->where('status', 'completed')->count() }}</b></div>
    <div>Presensi hadir<b>{{ $rekapPresensi->where('status', 'hadir')->count() }} / {{ $rekapPresensi->count() }}</b></div>
    <div>Upah belum dibayar<b>Rp {{ number_format((float) $rekapUpah->where('status_pembayaran', 'belum_dibayar')->sum('jumlah_upah'), 0, ',', '.') }}</b></div>
  </div>

  <h2>Jadwal Kegiatan</h2>
  <table>
    <thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Jenis Pekerjaan</th><th>Lokasi</th><th>Status</th></tr></thead>
    <tbody>
      @forelse ($rekapJadwal as $item)
        <tr>
          <td>{{ $item->tanggal?->format('d-m-Y') }}</td>
          <td>{{ $item->nama_kegiatan }}</td>
          <td>{{ $item->jenisPekerjaan->nama_pekerjaan ?? '-' }}</td>
          <td>{{ $item->lokasi }}</td>
          <td>{{ ['draft' => 'Draf', 'scheduled' => 'Terjadwal', 'ongoing' => 'Sedang berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty">Tidak ada data pada hasil filter.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Penugasan</h2>
  <table>
    <thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Anggota</th><th>Status Tugas</th><th>Presensi</th><th class="num">Upah</th></tr></thead>
    <tbody>
      @forelse ($rekapTugas as $item)
        <tr>
          <td>{{ $item->jadwal->tanggal?->format('d-m-Y') }}</td>
          <td>{{ $item->jadwal->nama_kegiatan }}</td>
          <td>{{ $item->anggota->nama_lengkap }}</td>
          <td>{{ ['assigned' => 'Ditugaskan', 'in_progress' => 'Sedang dikerjakan', 'waiting_verification' => 'Menunggu pemeriksaan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$item->status] ?? $item->status }}</td>
          <td>{{ ['hadir' => 'Hadir', 'izin' => 'Izin', 'tidak_hadir' => 'Tidak hadir'][$item->presensi?->status] ?? 'Belum dicatat' }}</td>
          <td class="num">{{ $item->upah ? 'Rp '.number_format((float) $item->upah->jumlah_upah, 0, ',', '.') : 'Belum dihitung' }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty">Tidak ada data pada hasil filter.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Presensi</h2>
  <table>
    <thead><tr><th>Kegiatan</th><th>Anggota</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Catatan</th></tr></thead>
    <tbody>
      @forelse ($rekapPresensi as $item)
        <tr>
          <td>{{ $item->penugasan->jadwal->nama_kegiatan }}</td>
          <td>{{ $item->penugasan->anggota->nama_lengkap }}</td>
          <td>{{ $item->waktu_check_in?->format('d-m-Y H:i') ?? '-' }}</td>
          <td>{{ $item->waktu_check_out?->format('d-m-Y H:i') ?? '-' }}</td>
          <td>{{ ['hadir' => 'Hadir', 'izin' => 'Izin', 'tidak_hadir' => 'Tidak hadir'][$item->status] ?? $item->status }}</td>
          <td>{{ $item->catatan ?: '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty">Tidak ada data pada hasil filter.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Upah</h2>
  <table>
    <thead><tr><th>Kegiatan</th><th>Anggota</th><th class="num">Jumlah</th><th>Status Pembayaran</th><th>Metode</th><th>Tanggal Bayar</th></tr></thead>
    <tbody>
      @forelse ($rekapUpah as $item)
        <tr>
          <td>{{ $item->penugasan->jadwal->nama_kegiatan }}</td>
          <td>{{ $item->penugasan->anggota->nama_lengkap }}</td>
          <td class="num">Rp {{ number_format((float) $item->jumlah_upah, 0, ',', '.') }}</td>
          <td>{{ $item->status_pembayaran === 'sudah_dibayar' ? 'Sudah dibayar' : 'Belum dibayar' }}</td>
          <td>{{ $item->metode_pembayaran ?? '-' }}</td>
          <td>{{ $item->waktu_pembayaran?->format('d-m-Y H:i') ?? '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="empty">Tidak ada data pada hasil filter.</td></tr>
      @endforelse
    </tbody>
  </table>

  <p class="foot">Dicetak {{ now()->format('d-m-Y H:i') }}</p>
</body>
</html>