/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jadwal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jadwal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jenis_pekerjaan_id` bigint unsigned NOT NULL,
  `nama_kegiatan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `lokasi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `waktu_mulai` time DEFAULT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `jumlah_orang_dibutuhkan` int unsigned NOT NULL DEFAULT '1',
  `jumlah_satuan` decimal(12,2) DEFAULT NULL,
  `metode_penjadwalan` enum('otomatis','manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','scheduled','ongoing','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `upah_per_anggota` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_upah` decimal(15,2) NOT NULL DEFAULT '0.00',
  `perlu_verifikasi` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `jadwal_jenis_pekerjaan_id_foreign` (`jenis_pekerjaan_id`),
  KEY `jadwal_created_by_foreign` (`created_by`),
  CONSTRAINT `jadwal_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jadwal_jenis_pekerjaan_id_foreign` FOREIGN KEY (`jenis_pekerjaan_id`) REFERENCES `jenis_pekerjaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jenis_pekerjaan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jenis_pekerjaan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_pekerjaan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `satuan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarif_default` decimal(15,2) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jenis_pekerjaan_created_by_foreign` (`created_by`),
  CONSTRAINT `jenis_pekerjaan_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kompetensi_anggota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kompetensi_anggota` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `anggota_id` bigint unsigned NOT NULL,
  `jenis_pekerjaan_id` bigint unsigned NOT NULL,
  `tingkat_kemampuan` enum('pemula','menengah','mahir') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kompetensi_anggota_anggota_id_jenis_pekerjaan_id_unique` (`anggota_id`,`jenis_pekerjaan_id`),
  KEY `kompetensi_anggota_jenis_pekerjaan_id_foreign` (`jenis_pekerjaan_id`),
  CONSTRAINT `kompetensi_anggota_anggota_id_foreign` FOREIGN KEY (`anggota_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kompetensi_anggota_jenis_pekerjaan_id_foreign` FOREIGN KEY (`jenis_pekerjaan_id`) REFERENCES `jenis_pekerjaan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `penugasan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penugasan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jadwal_id` bigint unsigned NOT NULL,
  `anggota_id` bigint unsigned NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `original_anggota_id` bigint unsigned DEFAULT NULL,
  `status` enum('assigned','in_progress','waiting_verification','completed','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sumber_penugasan` enum('otomatis','manual','hasil_perubahan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `catatan_penyelesaian` text COLLATE utf8mb4_unicode_ci,
  `bukti_selesai` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `catatan_verifikasi` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `catatan_perubahan` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `penugasan_jadwal_id_foreign` (`jadwal_id`),
  KEY `penugasan_anggota_id_foreign` (`anggota_id`),
  KEY `penugasan_original_anggota_id_foreign` (`original_anggota_id`),
  KEY `penugasan_verified_by_foreign` (`verified_by`),
  CONSTRAINT `penugasan_anggota_id_foreign` FOREIGN KEY (`anggota_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `penugasan_jadwal_id_foreign` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `penugasan_original_anggota_id_foreign` FOREIGN KEY (`original_anggota_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `penugasan_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permintaan_perubahan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permintaan_perubahan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `penugasan_id` bigint unsigned NOT NULL,
  `diajukan_oleh` bigint unsigned NOT NULL,
  `jenis_permintaan` enum('tukar','sanggah') COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_penugasan_id` bigint unsigned DEFAULT NULL,
  `target_jadwal_id` bigint unsigned DEFAULT NULL,
  `alasan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `diproses_oleh` bigint unsigned DEFAULT NULL,
  `diproses_at` datetime DEFAULT NULL,
  `catatan_admin` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permintaan_perubahan_penugasan_id_foreign` (`penugasan_id`),
  KEY `permintaan_perubahan_diajukan_oleh_foreign` (`diajukan_oleh`),
  KEY `permintaan_perubahan_target_penugasan_id_foreign` (`target_penugasan_id`),
  KEY `permintaan_perubahan_diproses_oleh_foreign` (`diproses_oleh`),
  KEY `permintaan_perubahan_target_jadwal_id_foreign` (`target_jadwal_id`),
  CONSTRAINT `permintaan_perubahan_diajukan_oleh_foreign` FOREIGN KEY (`diajukan_oleh`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permintaan_perubahan_diproses_oleh_foreign` FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `permintaan_perubahan_penugasan_id_foreign` FOREIGN KEY (`penugasan_id`) REFERENCES `penugasan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permintaan_perubahan_target_jadwal_id_foreign` FOREIGN KEY (`target_jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE SET NULL,
  CONSTRAINT `permintaan_perubahan_target_penugasan_id_foreign` FOREIGN KEY (`target_penugasan_id`) REFERENCES `penugasan` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `presensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presensi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `penugasan_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_check_in` datetime DEFAULT NULL,
  `waktu_check_out` datetime DEFAULT NULL,
  `status` enum('hadir','tidak_hadir','izin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hadir',
  `coverage_accepted` tinyint(1) NOT NULL DEFAULT '0',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `presensi_penugasan_id_tanggal_unique` (`penugasan_id`,`tanggal`),
  CONSTRAINT `presensi_penugasan_id_foreign` FOREIGN KEY (`penugasan_id`) REFERENCES `penugasan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rekomendasi_penugasan_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rekomendasi_penugasan_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jadwal_id` bigint unsigned NOT NULL,
  `anggota_id` bigint unsigned NOT NULL,
  `algoritma` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `peringkat` int unsigned NOT NULL,
  `skor` decimal(8,6) NOT NULL,
  `rincian` json NOT NULL,
  `dipilih` tinyint(1) NOT NULL,
  `dibuat_oleh` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `rekomendasi_penugasan_log_jadwal_id_foreign` (`jadwal_id`),
  KEY `rekomendasi_penugasan_log_anggota_id_foreign` (`anggota_id`),
  KEY `rekomendasi_penugasan_log_dibuat_oleh_foreign` (`dibuat_oleh`),
  CONSTRAINT `rekomendasi_penugasan_log_anggota_id_foreign` FOREIGN KEY (`anggota_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rekomendasi_penugasan_log_dibuat_oleh_foreign` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rekomendasi_penugasan_log_jadwal_id_foreign` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `upah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `upah` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `penugasan_id` bigint unsigned NOT NULL,
  `mode_perhitungan` enum('otomatis','manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tarif_per_satuan` decimal(15,2) DEFAULT NULL,
  `jumlah_satuan` decimal(12,2) DEFAULT NULL,
  `jumlah_upah` decimal(15,2) NOT NULL,
  `status_pembayaran` enum('belum_dibayar','sudah_dibayar') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum_dibayar',
  `metode_pembayaran` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_pembayaran` date DEFAULT NULL,
  `waktu_pembayaran` datetime DEFAULT NULL,
  `dibayar_oleh` bigint unsigned DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `upah_penugasan_id_unique` (`penugasan_id`),
  KEY `upah_dibayar_oleh_foreign` (`dibayar_oleh`),
  CONSTRAINT `upah_dibayar_oleh_foreign` FOREIGN KEY (`dibayar_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `upah_penugasan_id_foreign` FOREIGN KEY (`penugasan_id`) REFERENCES `penugasan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nomor_anggota` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor_wa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'anggota',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_nomor_anggota_unique` (`nomor_anggota`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_09_19_000000_modify_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_09_19_000001_create_jenis_pekerjaan_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_09_19_000002_create_kompetensi_anggota_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_09_19_000003_create_jadwal_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_09_19_000004_create_penugasan_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_09_19_000005_create_presensi_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_09_19_000006_create_upah_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_09_19_000007_create_permintaan_perubahan_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_09_20_000000_add_metode_pembayaran_to_upah_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_09_20_000001_add_waktu_pembayaran_to_upah_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_10_09_063609_simplify_categories_and_change_request_targets',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_10_09_091055_add_pay_and_verification_options_to_jadwal',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_10_09_223822_add_total_upah_to_jadwal_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_10_10_000001_add_multi_day_schedule_attendance',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_10_21_104305_add_report_rejection_and_daily_coverage_acceptance',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_11_01_150846_create_rekomendasi_penugasan_log_table',1);
