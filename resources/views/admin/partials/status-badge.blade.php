@php
  $maps = [
    'jadwal' => [
      'draft'     => ['Draf', 'badge-muted'],
      'scheduled' => ['Terjadwal', 'badge'],
      'ongoing'   => ['Sedang berlangsung', 'badge'],
      'completed' => ['Selesai', 'badge'],
      'cancelled' => ['Dibatalkan', 'badge-danger'],
    ],
    'tugas' => [
      'assigned'             => ['Ditugaskan', 'badge-muted'],
      'in_progress'          => ['Sedang dikerjakan', 'badge'],
      'waiting_verification' => ['Menunggu pemeriksaan', 'badge-warn'],
      'completed'            => ['Selesai', 'badge'],
      'cancelled'            => ['Dibatalkan', 'badge-danger'],
    ],
    'presensi' => [
      'hadir'       => ['Hadir', 'badge'],
      'izin'        => ['Izin', 'badge-warn'],
      'tidak_hadir' => ['Tidak hadir', 'badge-danger'],
      'belum'       => ['Belum dicatat', 'badge-muted'],
    ],
    'bayar' => [
      'sudah_dibayar' => ['Sudah dibayar', 'badge'],
      'belum_dibayar' => ['Belum dibayar', 'badge-warn'],
    ],
        'permintaan' => [
      'pending'  => ['Menunggu diproses', 'badge-warn'],
      'approved' => ['Disetujui', 'badge'],
      'rejected' => ['Ditolak', 'badge-danger'],
    ],
  ];
  [$label, $class] = $maps[$type][$status] ?? [ucfirst(str_replace('_', ' ', (string) $status)), 'badge-muted'];
@endphp
<span class="{{ $class }}">{{ $label }}</span>