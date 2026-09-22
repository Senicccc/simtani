<?php

use App\Http\Controllers\Admin\AnggotaController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\JenisPekerjaanController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PenugasanController;
use App\Http\Controllers\Admin\PermintaanPerubahanController as AdminPermintaanPerubahanController;
use App\Http\Controllers\Admin\PresensiController as AdminPresensiController;
use App\Http\Controllers\Admin\UpahController as AdminUpahController;
use App\Http\Controllers\Anggota\PermintaanPerubahanController as AnggotaPermintaanPerubahanController;
use App\Http\Controllers\Anggota\PresensiController as AnggotaPresensiController;
use App\Http\Controllers\Anggota\TugasController;
use App\Http\Controllers\Anggota\UpahController as AnggotaUpahController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return redirect()->route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'anggota.dashboard');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

        Route::get('/anggota', [AnggotaController::class, 'index'])->name('anggota.index');
        Route::get('/anggota/create', [AnggotaController::class, 'create'])->name('anggota.create');
        Route::post('/anggota', [AnggotaController::class, 'store'])->name('anggota.store');
        Route::get('/anggota/{anggota}/edit', [AnggotaController::class, 'edit'])->name('anggota.edit');
        Route::put('/anggota/{anggota}', [AnggotaController::class, 'update'])->name('anggota.update');

        Route::get('/jenis-pekerjaan', [JenisPekerjaanController::class, 'index'])->name('jenis-pekerjaan.index');
        Route::get('/jenis-pekerjaan/create', [JenisPekerjaanController::class, 'create'])->name('jenis-pekerjaan.create');
        Route::post('/jenis-pekerjaan', [JenisPekerjaanController::class, 'store'])->name('jenis-pekerjaan.store');
        Route::get('/jenis-pekerjaan/{jenisPekerjaan}/edit', [JenisPekerjaanController::class, 'edit'])->name('jenis-pekerjaan.edit');
        Route::put('/jenis-pekerjaan/{jenisPekerjaan}', [JenisPekerjaanController::class, 'update'])->name('jenis-pekerjaan.update');

        Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
        Route::get('/jadwal/create', [JadwalController::class, 'create'])->name('jadwal.create');
        Route::post('/jadwal', [JadwalController::class, 'store'])->name('jadwal.store');
        Route::get('/jadwal/{jadwal}', [JadwalController::class, 'show'])->name('jadwal.show');
        Route::get('/jadwal/{jadwal}/edit', [JadwalController::class, 'edit'])->name('jadwal.edit');
        Route::put('/jadwal/{jadwal}', [JadwalController::class, 'update'])->name('jadwal.update');
        Route::post('/jadwal/{jadwal}/generate', [JadwalController::class, 'generate'])->name('jadwal.generate');
        Route::get('/jadwal/{jadwal}/penugasan', [JadwalController::class, 'penugasan'])->name('jadwal.penugasan');

        Route::get('/penugasan', [PenugasanController::class, 'index'])->name('penugasan.index');
        Route::get('/penugasan/create', [PenugasanController::class, 'create'])->name('penugasan.create');
        Route::post('/penugasan', [PenugasanController::class, 'store'])->name('penugasan.store');
        Route::get('/penugasan/{penugasan}', [PenugasanController::class, 'show'])->name('penugasan.show');
        Route::get('/penugasan/{penugasan}/edit', [PenugasanController::class, 'edit'])->name('penugasan.edit');
        Route::put('/penugasan/{penugasan}', [PenugasanController::class, 'update'])->name('penugasan.update');
        Route::post('/penugasan/{penugasan}/verify', [PenugasanController::class, 'verify'])->name('penugasan.verify');

        Route::get('/presensi', [AdminPresensiController::class, 'index'])->name('presensi.index');

        Route::get('/upah', [AdminUpahController::class, 'index'])->name('upah.index');
        Route::get('/upah/{upah}/edit', [AdminUpahController::class, 'edit'])->name('upah.edit');
        Route::put('/upah/{upah}', [AdminUpahController::class, 'update'])->name('upah.update');
        Route::post('/upah/{upah}/mark-paid', [AdminUpahController::class, 'markPaid'])->name('upah.mark-paid');

        Route::get('/permintaan-perubahan', [AdminPermintaanPerubahanController::class, 'index'])->name('permintaan-perubahan.index');
        Route::get('/permintaan-perubahan/{permintaan}', [AdminPermintaanPerubahanController::class, 'show'])->name('permintaan-perubahan.show');
        Route::post('/permintaan-perubahan/{permintaan}/process', [AdminPermintaanPerubahanController::class, 'process'])->name('permintaan-perubahan.process');

        Route::get('/riwayat-tugas', function () {
            return view('admin.riwayat-tugas');
        })->name('riwayat-tugas');

        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/export', [LaporanController::class, 'export'])->name('laporan.export');

        Route::get('/profil', [ProfilController::class, 'admin'])->name('profil');
        Route::put('/profil', [ProfilController::class, 'updateAdmin'])->name('profil.update');
    });

    Route::prefix('anggota')->name('anggota.')->middleware('role:anggota')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'anggota'])->name('dashboard');

        Route::get('/tugas', [TugasController::class, 'index'])->name('tugas.index');
        Route::get('/tugas/{penugasan}', [TugasController::class, 'show'])->name('tugas.show');
        Route::post('/tugas/{penugasan}/mulai', [TugasController::class, 'mulai'])->name('tugas.mulai');
        Route::get('/tugas/{penugasan}/selesai', [TugasController::class, 'showComplete'])->name('tugas.complete.form');
        Route::post('/tugas/{penugasan}/selesai', [TugasController::class, 'complete'])->name('tugas.complete');

        Route::get('/riwayat-tugas', [TugasController::class, 'riwayat'])->name('riwayat-tugas');

        Route::get('/presensi', [AnggotaPresensiController::class, 'index'])->name('presensi.index');
        Route::post('/presensi/{penugasan}/check-in', [AnggotaPresensiController::class, 'checkIn'])->name('presensi.check-in');
        Route::post('/presensi/{penugasan}/check-out', [AnggotaPresensiController::class, 'checkOut'])->name('presensi.check-out');
        Route::post('/presensi/{penugasan}/status', [AnggotaPresensiController::class, 'setStatus'])->name('presensi.status');

        Route::get('/hubungi-admin', [TugasController::class, 'contactAdmin'])->name('hubungi-admin');

        Route::get('/upah', [AnggotaUpahController::class, 'index'])->name('upah.index');

        Route::get('/permintaan-perubahan', [AnggotaPermintaanPerubahanController::class, 'index'])->name('permintaan-perubahan.index');
        Route::get('/permintaan-perubahan/create', [AnggotaPermintaanPerubahanController::class, 'create'])->name('permintaan-perubahan.create');
        Route::post('/permintaan-perubahan', [AnggotaPermintaanPerubahanController::class, 'store'])->name('permintaan-perubahan.store');

        Route::get('/profil', [ProfilController::class, 'anggota'])->name('profil');
        Route::put('/profil', [ProfilController::class, 'updateAnggota'])->name('profil.update');
    });
});
