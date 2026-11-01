<?php

return [
    'algoritma' => env('SMART_ALGORITMA', 'smart'),
    'urutan_kriteria' => ['beban', 'jeda', 'kompetensi', 'kehadiran'],
    'jendela_beban_mundur_hari' => 30,
    'jendela_beban_maju_hari' => 30,
    'jendela_kehadiran_hari' => 90,
    'jeda_maksimum_hari' => 30,
    'jam_bawaan_per_hari' => 6,
];
