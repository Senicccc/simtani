<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIMTANI — @yield('title')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

<div id="app-shell">
  <div class="mobile-topbar">
    <button class="icon-btn" id="btn-menu" aria-label="Buka menu">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <div class="mobile-topbar-brand"><img src="{{ asset('images/Logo.jpg') }}" alt="" class="brand-logo">SIMTANI</div>
  </div>

  <div id="sidebar-backdrop"></div>

  <aside class="sidebar" id="sidebar">
    <div class="brand"><img src="{{ asset('images/Logo.jpg') }}" alt="Logo SIMTANI" class="brand-logo">SIMTANI</div>

    @auth
    <nav>
      @if (auth()->user()->role === 'admin')
        <a href="{{ route('admin.dashboard') }}" class="nav-item @if(request()->routeIs('admin.dashboard')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10"/></svg>
          <span>Dashboard</span>
        </a>

        <div class="nav-label">Kelola</div>
        <a href="{{ route('admin.anggota.index') }}" class="nav-item @if(request()->routeIs('admin.anggota.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="3"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><circle cx="17" cy="8" r="2.5"/><path d="M22 20c0-2.8-2-4.5-4.5-4.8"/></svg>
          <span>Anggota</span>
        </a>
        <a href="{{ route('admin.jenis-pekerjaan.index') }}" class="nav-item @if(request()->routeIs('admin.jenis-pekerjaan.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a3 3 0 1 1-4.4 4.1L4 17v3h3l6.6-6.6a3 3 0 1 1 4.1-4.4l3-2.7-2.3-2.3-2.7 3Z"/></svg>
          <span>Kategori Pekerjaan</span>
        </a>
        <a href="{{ route('admin.jadwal.index') }}" class="nav-item @if(request()->routeIs('admin.jadwal.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
          <span>Jadwal</span>
        </a>
        <a href="{{ route('admin.penugasan.index') }}" class="nav-item @if(request()->routeIs('admin.penugasan.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6M9 16h6M9 8h3"/><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/></svg>
          <span>Penugasan</span>
        </a>

        <div class="nav-label">Operasional</div>
        <a href="{{ route('admin.presensi.index') }}" class="nav-item @if(request()->routeIs('admin.presensi.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12l3 3 6-6"/></svg>
          <span>Presensi</span>
        </a>
        <a href="{{ route('admin.upah.index') }}" class="nav-item @if(request()->routeIs('admin.upah.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/><path d="M3 7v10a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1h-5a2 2 0 1 0 0 4h5"/></svg>
          <span>Upah</span>
        </a>
        <a href="{{ route('admin.permintaan-perubahan.index') }}" class="nav-item @if(request()->routeIs('admin.permintaan-perubahan.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l4 4-4 4M3 11V9a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          <span>Permintaan Perubahan</span>
        </a>
        <a href="{{ route('admin.riwayat-tugas') }}" class="nav-item @if(request()->routeIs('admin.riwayat-tugas')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          <span>Riwayat Tugas</span>
        </a>
        <a href="{{ route('admin.laporan.index') }}" class="nav-item @if(request()->routeIs('admin.laporan.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M9 12h6M9 16h6M9 8h3"/></svg>
          <span>Laporan</span>
        </a>

        <div class="nav-label">Akun</div>
        <a href="{{ route('admin.profil') }}" class="nav-item @if(request()->routeIs('admin.profil')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
          <span>Profil</span>
        </a>
      @else
        <a href="{{ route('anggota.dashboard') }}" class="nav-item @if(request()->routeIs('anggota.dashboard')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10"/></svg>
          <span>Dashboard</span>
        </a>
        <a href="{{ route('anggota.tugas.index') }}" class="nav-item @if(request()->routeIs('anggota.tugas.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
          <span>Tugas</span>
        </a>
        <a href="{{ route('anggota.presensi.index') }}" class="nav-item @if(request()->routeIs('anggota.presensi.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12l3 3 6-6"/></svg>
          <span>Presensi</span>
        </a>
        <a href="{{ route('anggota.upah.index') }}" class="nav-item @if(request()->routeIs('anggota.upah.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/><path d="M3 7v10a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1h-5a2 2 0 1 0 0 4h5"/></svg>
          <span>Upah</span>
        </a>
        <a href="{{ route('anggota.riwayat-tugas') }}" class="nav-item @if(request()->routeIs('anggota.riwayat-tugas')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          <span>Riwayat Tugas</span>
        </a>
        <a href="{{ route('anggota.permintaan-perubahan.index') }}" class="nav-item @if(request()->routeIs('anggota.permintaan-perubahan.*')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l4 4-4 4M3 11V9a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 13v2a4 4 0 0 1-4 4H3"/></svg>
          <span>Permintaan Perubahan</span>
        </a>
        <a href="{{ route('anggota.hubungi-admin') }}" class="nav-item @if(request()->routeIs('anggota.hubungi-admin')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <span>Hubungi Admin</span>
        </a>

        <div class="nav-label">Akun</div>
        <a href="{{ route('anggota.profil') }}" class="nav-item @if(request()->routeIs('anggota.profil')) active @endif">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
          <span>Profil</span>
        </a>
      @endif
    </nav>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="logout">Keluar ({{ auth()->user()->nama_lengkap }})</button>
    </form>
    @endauth
  </aside>

  <main id="main-content">
    <section class="view active">
      @if (session('success'))
        <div class="banner-success">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="banner-success" style="background:var(--amber-100);color:var(--amber-600)">
          <ul style="margin:0;padding-left:16px">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
      @yield('content')
    </section>
  </main>
</div>

<script src="{{ asset('js/script.js') }}"></script>
</body>
</html>
