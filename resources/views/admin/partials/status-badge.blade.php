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
  ];
  [$label, $class] = $maps[$type][$status] ?? [$status, 'badge-muted'];
@endphp
<span class="{{ $class }}">{{ $label }}</span>