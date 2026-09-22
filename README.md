# SIMTANI

SIMTANI adalah aplikasi Laravel untuk mengelola anggota, kegiatan pertanian, penugasan, presensi, upah, dan laporan.

## Menjalankan aplikasi

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Untuk frontend:

```bash
npm install
npm run dev
```

## Fitur yang tersedia

### Admin

- Dashboard admin.
- CRUD jadwal/kegiatan dengan kategori pekerjaan, lokasi, tanggal, waktu, kebutuhan anggota, dan jumlah satuan.
- CRUD kategori pekerjaan untuk kebutuhan desa seperti pemupukan, berkebun, atau ternak.
- Tambah anggota dengan nomor anggota otomatis.
- Status anggota aktif dan nonaktif, termasuk filter daftar anggota.
- Penugasan manual tanpa validasi kompetensi.
- Detail penugasan dikelompokkan per kegiatan dan menampilkan seluruh anggota.
- Keluarkan anggota dari kegiatan dengan pilihan mengganti anggota atau membiarkan slot kosong.
- Ubah anggota, status tugas, dan catatan penugasan.
- Verifikasi tugas yang sudah dikirim anggota.
- Lihat presensi anggota secara read-only.
- Kelola upah, status pembayaran, metode pembayaran, dan penanda pembayaran.
- Waktu dan tanggal pembayaran dicatat otomatis oleh server saat pembayaran ditandai lunas.
- Laporan dengan filter tanggal, kategori, nama jadwal, anggota/penugasan, dan daftar kegiatan.
- Halaman laporan siap dicetak atau disimpan sebagai PDF melalui browser.

### Anggota

- Dashboard anggota.
- Melihat tugas yang diberikan dan detail anggota satu kegiatan.
- Mengubah status tugas menjadi `in_progress` melalui tombol Mulai tugas.
- Mengirim tugas selesai untuk menunggu verifikasi admin.
- Mengisi presensi `hadir`, `izin`, atau `tidak_hadir`, serta check-out.
- Melihat riwayat tugas dan upah beserta waktu pembayaran.
- Mengajukan permintaan perubahan penugasan.
- Menghubungi admin melalui WhatsApp atau email.

## Status penugasan

Alur normal penugasan adalah:

1. `assigned`: anggota sudah ditugaskan.
2. `in_progress`: anggota menekan Mulai tugas.
3. `waiting_verification`: anggota mengirim laporan selesai.
4. `completed`: admin memeriksa dan menekan Verifikasi selesai.

Admin dapat mengeluarkan anggota. Data lama disimpan sebagai `cancelled`; jika ada pengganti, sistem membuat penugasan baru untuk anggota tersebut.

## Fitur untuk frontend yang masih coming soon

Route berikut sudah tersedia untuk integrasi frontend, tetapi UI utamanya masih bertanda `Coming soon`:

- Penjadwalan otomatis: `POST /admin/jadwal/{jadwal}/generate`, route `admin.jadwal.generate`.
- Halaman penugasan otomatis: `GET /admin/jadwal/{jadwal}/penugasan`, route `admin.jadwal.penugasan`.

Penjadwalan otomatis menggunakan `AutoScheduleService` dan dapat dikembangkan lebih lanjut untuk aturan pemerataan beban kerja.

## Pengujian

```bash
php artisan test
```
"# simtani" 
# simtani
