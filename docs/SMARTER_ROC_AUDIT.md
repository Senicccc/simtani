# Audit SMARTER-ROC SIM-Tani

## Ringkasan kode dan alur

- `AutoScheduleService::recommendationsForSchedule()` menghitung vacancy melalui `JadwalStatusService::dailyCoverage()`, lalu menggunakan filter anggota yang sama pada jalur SMART dan legacy. Jalur legacy mempertahankan urutan lama; jalur SMART menghitung kriteria, utilitas, bobot ROC, skor, dan peringkat.
- `generateForSchedule()` membuat penugasan dan snapshot rekomendasi dalam transaksi. Route, controller, view, dan migration lama tidak diubah.
- `SmartCriteriaService` membaca penugasan, kompetensi, dan presensi secara berkelompok. Migration penugasan memiliki status `rejected` terpisah dari `cancelled`; migration multi-hari menambahkan rentang tanggal per penugasan dan tanggal presensi.
- Skema aktual membuat `jadwal.tanggal` wajib (bukan nullable), sementara `tanggal_selesai`, rentang tanggal penugasan, dan jam jadwal boleh null. `presensi` memiliki status `hadir`, `tidak_hadir`, dan `izin`; migration multi-hari menambahkan tanggal per catatan presensi.
- Log lama menyimpan skor sebagai `DECIMAL(8,6)` dan tidak memiliki foreign key ke penugasan yang dibuat. Rincian SMART sebelumnya tidak mencatat bobot/parameter; `reason` juga tidak memuat skor.

## Temuan implementasi yang diperbaiki

1. **Dampak tinggi — perhitungan penilaian dan reproduksibilitas:** scorer sebelumnya menerima bobot/kriteria kandidat tanpa validasi lengkap, meng-clamp nilai di luar rentang, dan memakai array union yang dapat mempertahankan nilai `skor`/`utilitas` dari input. Kini input diverifikasi, nilai di luar domain ditolak, dan key hasil dibangun tanpa tertimpa data input.
2. **Dampak tinggi — konsistensi evaluasi C1:** `smart:evaluasi` memanggil helper beban dengan status `null`, sehingga menghitung semua status selain `cancelled`, termasuk `rejected`; C1 rekomendasi hanya menghitung `assigned`, `in_progress`, `waiting_verification`, dan `completed`. Evaluasi kini memakai daftar status C1 yang sama. Rasio maksimum/rata-rata sekarang `N/A` ketika mean nol, bukan angka 0 yang menyesatkan.
3. **Dampak sedang — tanggal efektif C1:** pemilihan data berdasarkan tanggal mulai jadwal induk dapat melewatkan penugasan dengan rentang parsial yang tanggal efektifnya beririsan jendela. Query kini menggunakan rentang tanggal efektif penugasan/jadwal, inklusif, dan melaporkan rentang terbalik sebagai data invalid.
4. **Dampak sedang — audit jurnal:** setiap rekomendasi SMART kini menyimpan nilai mentah, utilitas, bobot, urutan, parameter, tanggal jadwal, dan skor presisi penuh dalam rincian JSON. Log kandidat terpilih menyimpan referensi ke `penugasan` yang benar-benar dibuat. Kolom `skor` lama tetap dipertahankan agar kompatibel; angka presisi penuh tersedia di JSON.
5. **Kontrak alasan:** key kandidat lama dipertahankan. Awalan `reason` sekarang menyatakan jumlah tugas dan kompetensi dengan pola yang stabil; jalur SMART menambahkan skor setelah alasan itu.
6. **Dampak sedang — data rusak dan batas waktu:** relasi jadwal yang hilang, jadwal target dengan tanggal terbalik, kompetensi di luar enum, konfigurasi jendela negatif, dan jam default tidak valid kini menghasilkan error eksplisit, bukan nilai seolah-olah valid.
7. **Dampak sedang — evaluasi periode:** hitungan sumber dan kompetensi sebelumnya dapat melewatkan penugasan dengan rentang parsial yang jatuh dalam periode tetapi tanggal jadwal induknya di luar periode. Query kini memakai rentang tanggal efektif yang sama; CSV diberi UTF-8 BOM untuk pembacaan spreadsheet.

## Status `rejected/ditolak`: temuan dan dampak

Status `rejected` ditambahkan terpisah melalui migration `2026_10_21_104305_add_report_rejection_and_daily_coverage_acceptance.php`. Alur penolakan laporan di `PenugasanController` mengubah presensi tanggal penyelesaian menjadi `tidak_hadir`, lalu mengubah status penugasan menjadi `rejected`. Status ini **tidak disamakan** dengan `cancelled`.

- **C1:** `rejected` tidak dihitung, sesuai allowlist baseline C1 yang hanya mencakup empat status kerja. Ini beda dari `cancelled` secara makna status, walaupun keduanya tidak menambah C1.
- **C2:** `rejected` ikut diperiksa karena definisi baseline hanya mengecualikan `cancelled`; jika memiliki tanggal efektif sebelum jadwal target, tanggal akhirnya dapat menjadi penugasan terakhir dan memperkecil nilai jeda sampai minimum 0. Ini berlaku pula pada evaluasi kode sebelum diperbaiki untuk C1; evaluasi kini menyamakan C1, bukan mengubah definisi C2.
- **C4:** tidak ada filter status penugasan pada agregasi presensi. Karena itu catatan presensi dari assignment `rejected` tetap masuk. Alur penolakan laporan mengubah presensi menjadi `tidak_hadir`, sehingga rasio C4 dapat turun. Catatan `hadir` yang sudah ada pada assignment `rejected` juga tetap masuk.
- **Pembatalan dan C4:** kode juga tidak mengecualikan presensi pada assignment `cancelled`. Baseline C4 belum mendefinisikan aturan untuk riwayat presensi setelah pembatalan; perilaku ini sengaja tidak diubah.
- Filter kandidat pada jadwal yang sama menganggap assignment `rejected` masih ada (hanya `cancelled` yang dikecualikan), sehingga anggota yang sama tidak otomatis ditugaskan ulang.

**Keputusan metodologis yang masih diperlukan:** tetapkan apakah `rejected` berarti pekerjaan tidak dilakukan (dan karena itu tidak layak memengaruhi C2/C4), atau status verifikasi yang ditolak setelah sebagian pekerjaan/tanggal benar-benar berlangsung. Opsi lain adalah mempertahankan baseline sekarang: C2 menghitung tanggal efektif, C4 memakai catatan presensi termasuk `tidak_hadir`. Perubahan pilihan ini mengubah utilitas dan ranking, jadi tidak diterapkan diam-diam. Tetapkan pula aturan C4 untuk assignment `cancelled` yang masih memiliki presensi.

Contoh test ilustratif: dua assignment `rejected` berakhir 10 dan 11 April untuk target 15 April menghasilkan C1=0 jam, C2=4 hari (assignment terakhir 11 April), dan C4=0,5 bila satu presensi `tidak_hadir` dan satu `hadir`. Jika `rejected` dikecualikan dari C2 seperti `cancelled`, nilai jeda pada contoh itu kembali ke imputasi 30 hari (selisih +26 hari). Jika hanya ada presensi `tidak_hadir` pada assignment `rejected`, C4 menjadi 0 alih-alih imputasi 1,0 saat tidak ada catatan terhitung. Angka ini merupakan dampak definisi atas fixture test, bukan hasil lapangan.

## Audit seluruh status penugasan dan presensi

Migration awal membatasi status penugasan ke `assigned`, `in_progress`, `waiting_verification`, `completed`, dan `cancelled`; migration penolakan laporan menambahkan `rejected` secara terpisah. Makna operasional yang dapat dibuktikan dari alur aplikasi:

- `assigned`: penugasan sudah dibuat, belum dimulai. Dibuat oleh penugasan otomatis/manual, hasil perubahan, atau pengganti harian.
- `in_progress`: anggota memulai tugas; saat itu presensi `hadir` dibuat/diperbarui.
- `waiting_verification`: laporan selesai terkirim dan menunggu admin, bila jadwal memerlukan verifikasi.
- `completed`: laporan disetujui admin atau selesai otomatis tanpa verifikasi.
- `rejected`: admin menolak laporan setelah status `waiting_verification`; presensi pada tanggal laporan diubah menjadi `tidak_hadir`. Ini tidak sama dengan pembatalan sebelum/dengan penutupan tugas.
- `cancelled`: assignment dikeluarkan/diganti, permintaan perpindahan/sanggah disetujui, atau assignment rejected lama ditutup ketika jadwal dibuka ulang dengan tenggat baru. Status ini tidak menghapus rekaman presensi.

| Status penugasan | Makna/status flow dalam kode | Dampak C1 | Dampak C2 | Dampak C4 |
|---|---|---|---|---|
| `assigned` | Penugasan dibuat dan belum dimulai | Masuk allowlist C1 jika tanggal efektif beririsan jendela | Ikut dicari jika bukan jadwal target dan tanggal akhirnya `<= Js` | Presensi terkait ikut; `hadir` numerator/denominator, `tidak_hadir` denominator |
| `in_progress` | Anggota memulai tugas; presensi hadir dicatat | Masuk C1 | Ikut dicari dengan aturan tanggal efektif | Presensi ikut dihitung |
| `waiting_verification` | Laporan selesai menunggu verifikasi | Masuk C1 | Ikut dicari dengan aturan tanggal efektif | Presensi ikut dihitung |
| `completed` | Selesai disetujui atau otomatis tanpa verifikasi | Masuk C1 | Ikut dicari dengan aturan tanggal efektif | Presensi ikut dihitung |
| `rejected` | Laporan diverifikasi dan ditolak; tanggal laporan ditandai `tidak_hadir` | Tidak masuk allowlist C1 | Tetap termasuk karena query C2 hanya mengecualikan `cancelled` | Tetap termasuk; penolakan mengubah presensi tanggal laporan ke `tidak_hadir`, menurunkan rasio |
| `cancelled` | Assignment dikeluarkan/ditutup; baris dan presensinya tetap ada | Tidak masuk allowlist C1 | Dikecualikan eksplisit oleh C2 | Presensi tetap dihitung karena query C4 tidak memfilter status penugasan; perlu keputusan domain bila pembatalan semestinya meniadakan presensi |

Presensi adalah dimensi terpisah dengan status `hadir`, `tidak_hadir`, atau `izin`. Untuk C4, `hadir` dihitung sebagai pembilang dan penyebut, `tidak_hadir` hanya penyebut, `izin` tidak masuk keduanya. Ketika tidak ada catatan `hadir/tidak_hadir`, baseline memberi nilai 1,0. C4 tidak melihat status assignment saat join presensi. `coverage_accepted` memengaruhi penerimaan kekurangan coverage harian, bukan definisi C1/C2/C4.

Transisi yang ditemukan: assignment dibuat dalam status `assigned`; anggota mengubahnya menjadi `in_progress`; pengiriman laporan mengubahnya menjadi `waiting_verification` atau `completed`; verifikasi admin mengubah `waiting_verification` menjadi `completed` atau `rejected`. Admin mengubah assignment menjadi `cancelled` ketika dikeluarkan/diganti; persetujuan request tukar/sanggah menutup assignment sumber sebagai `cancelled`; memperpanjang jadwal yang sudah selesai setelah penolakan laporan juga mengubah assignment rejected lama menjadi cancelled dan mengharuskan penugasan baru. Penolakan atas *permintaan perubahan* (`PermintaanPerubahan.status = rejected`) hanya menutup permintaan, bukan penugasan; jangan samakan dengan `Penugasan.status = rejected`.

Kode pendukung untuk verifikasi: `AutoScheduleService` dan `SmartCriteriaService` menentukan filter kandidat/kriteria; `Anggota\TugasController` memulai/melaporkan tugas; `Anggota\PresensiController` mencatat izin/tidak hadir; `Admin\PenugasanController` membuat, membatalkan, memverifikasi, dan menerima coverage; `Admin\PermintaanPerubahanController` memindahkan assignment; `Admin\JadwalController` menutup assignment rejected lama saat membuka kembali jadwal; `JadwalStatusService` menghitung coverage/status jadwal. Migration penugasan dan presensi merupakan batas nilai status serta struktur tanggalnya.

Tidak ada perubahan kebijakan C1/C2/C4 pada audit ini. Keputusan pemilik proses tetap diperlukan untuk C2/C4 rejected dan presensi pasca-cancelled; table di atas menyatakan perilaku implementasi aktual, bukan rekomendasi bahwa perilaku tersebut benar secara domain.

## Asumsi baseline dan keputusan pemilik proses

- Konfigurasi `['beban', 'jeda', 'kompetensi', 'kehadiran']` adalah baseline peneliti dengan C1 sebagai batas kebijakan, bukan hasil elicitation yang ditemukan di repository. Tidak ada bukti responden, tanggal, atau konsensus urutan swing C2–C4 di kode/data yang diperiksa.
- Imputasi tidak ada riwayat C2=30 hari, tanpa presensi terhitung C4=1, kompetensi kosong=1, default 6 jam/hari, jendela C1 −30/+30, jendela presensi 90 hari, hitung hari kalender, normalisasi C1 per jadwal, dan utilitas linear/aditif tetap merupakan asumsi baseline.
- Jadwal multi-hari memakai minimum coverage harian untuk menentukan vacancy, sedangkan assignment baru tanpa tanggal parsial mewarisi seluruh rentang jadwal. Bila vacancy antarhari berbeda, assignment penuh dapat menghasilkan tenaga berlebih pada hari dengan coverage lebih tinggi. Kebijakan assignment parsial/per hari belum dipilih dan tidak diubah.
- Log rekomendasi lama tetap terikat cascade pada jadwal dan anggota sebagaimana migration awal. Referensi baru ke penugasan `nullOnDelete`; jika penugasan dihapus, nilai snapshot dan `dipilih` bertahan tetapi FK assignment menjadi null. Kebijakan retensi/hard-delete audit masih perlu keputusan.
- Tidak ada hasil simulasi sensitivitas, elicitation stakeholder, validasi lapangan, atau bukti untuk mengklaim penerimaan jurnal. Nilai contoh algoritma adalah ilustrasi, bukan data lapangan.

## Seeder dan simulasi

Seeder lama tidak sesuai baseline: hanya menyediakan 14 anggota aktif, membuat jadwal sebelum 2 November, dan tidak mencakup kalender 14 hari, rentang multi-hari, atau jadwal tanpa jam. Seeder diperbarui agar:

- menyediakan 25 anggota simulasi aktif (ANG-009 tetap nonaktif), 44 jadwal bertanggal 2–15 November 2026, 2 jadwal multi-hari, dan 2 jadwal tanpa jam;
- memakai urutan kompetensi deterministik dengan seed tetap `20261102`, menghasilkan 38 pemula, 56 menengah, dan 31 mahir dari 125 kombinasi anggota/jenis pekerjaan (30,4%/44,8%/24,8%);
- mencakup presensi `hadir`, `tidak_hadir`, `izin`, penugasan dibatalkan, dan penugasan mendatang;
- menolak lingkungan selain local/testing dan menolak database yang memiliki jadwal non-simulasi; tidak menghapus jadwal pengguna;
- memakai prefix khusus untuk jadwal simulasi, menolak menimpa kompetensi berbeda, dan aman dijalankan ulang pada dataset miliknya;
- menjalankan `DatabaseSeeder` secara transaksional dan menggunakan `firstOrCreate`, sehingga akun/jenis pekerjaan yang sudah ada tidak ditimpa bila proses gagal atau dijalankan ulang.

Seeder **tidak dijalankan terhadap database aplikasi**. Dataset hanya dijalankan melalui test SQLite `:memory:`. Data itu sintetis dan tidak boleh disebut data lapangan atau hasil empiris.

## File perubahan

- `app/Services/SmartScoringService.php`: validasi ketat dan ranking deterministik tanpa clamp nilai invalid.
- `app/Services/SmartCriteriaService.php`: irisan tanggal efektif, validasi rentang, default waktu aman, serta pemisahan status C1 dan C2.
- `app/Services/AutoScheduleService.php`: rincian SMART lengkap, alasan dengan skor, dan referensi penugasan pada log.
- `app/Console/Commands/SmartScheduleEvaluationCommand.php`: total jam dengan allowlist C1 dan rasio aman ketika mean nol.
- `database/seeders/DatabaseSeeder.php` dan `database/seeders/SmartDemoSeeder.php`: populasi simulasi deterministik, data terscope, dan penolakan eksekusi yang berisiko.
- Migration baru: menautkan kandidat terpilih ke assignment yang benar-benar dibuat.
- Test baru: contoh hitung tangan, validasi scorer dan key collision, status `rejected/cancelled`, tanggal inklusif/parsial/batas jendela, imputasi, filter kandidat, generate/preview, rollback transaksi, output CSV, seeder deterministik/idempoten, dan migration baru.
- `phpunit.xml`: test dikunci ke SQLite in-memory agar tidak mengakses database lingkungan.

## Audit migration baru dan evaluasi

Migration tambahan hanya menambahkan `penugasan_id` nullable pada tabel log yang sudah ada, dengan foreign key ke `penugasan` dan aksi `ON DELETE SET NULL`. Baris log lama aman dari backfill karena nilai barunya null. `down()` menghapus foreign key dan kolom, tanpa mengubah migration lama atau data aplikasi. Test feature memeriksa nullable, referensi FK, null-on-delete, dan rollback di SQLite in-memory. Bentuk Laravel Schema Builder yang digunakan merupakan sintaks lintas-driver, tetapi pengujian pada server MySQL tidak tersedia dan migration tidak diterapkan ke database mana pun.

Command evaluasi menghitung jam menggunakan helper C1 yang sama (empat status, irisan inklusif, jam default, anggota aktif termasuk jam nol). Jumlah per sumber adalah hitungan **record penugasan non-cancelled untuk semua status selain cancelled**, termasuk rejected; metrik ini bukan jumlah jam C1. Kompetensi yang tidak tercatat diperlakukan sebagai level 1 pada ringkasan rata-rata sesuai imputasi baseline. Label tabel/CSV dan ringkasan terminal menyebut status hitungan sumber secara eksplisit. Test mengecek nilai jam periode, rejected/cancelled, rentang parsial, kasus jam nol, populasi aktif kosong, dan header/encoding CSV.

Test AutoSchedule membuktikan preview tidak menulis log, jumlah rekomendasi tidak melebihi slot, generate membuat assignment dan snapshot kandidat terpilih/tidak terpilih dengan hubungan yang sesuai, generate ulang tidak menggandakan record, dan jalur legacy tetap menghasilkan daftar/log legacy. Test tersebut menggunakan skema minimal SQLite buatan test, bukan migration produksi.

## Validasi dan batas klaim penelitian

Rumus SMART/ROC, urutan baseline, utilitas, imputasi, dan parameter metodologis tidak diubah. Urutan yang dikonfigurasi adalah `['beban', 'jeda', 'kompetensi', 'kehadiran']`; bukti elicitation stakeholder, responden, tanggal, dan konsensus swing tidak ditemukan. Ini tetap asumsi baseline peneliti, bukan preferensi stakeholder tervalidasi. Tidak ada hasil simulasi pembanding, sensitivitas statistik, validasi lapangan, atau penilaian ahli yang dihasilkan dalam pekerjaan ini.

Validasi akhir: `php artisan test` lulus dengan **40 test dan 208 assertion**; `vendor/bin/pint --test` lulus untuk seluruh file PHP yang berubah. Test seeder berjalan pada SQLite `:memory:`; migration aplikasi tidak dijalankan terhadap database aplikasi. MySQL, transaksi rollback di MySQL, dan alur browser/controller end-to-end tidak tersedia/dijalankan.

Kualitas implementasi dan test ini tidak membuktikan kelayakan atau penerimaan jurnal SINTA 4. Validasi data lapangan, konfirmasi stakeholder terhadap swing/bobot, keputusan atas status `rejected`/presensi `cancelled`, analisis sensitivitas imputasi, evaluasi komparatif, dan persetujuan etik/proses penelitian tetap diperlukan.
