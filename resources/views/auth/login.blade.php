<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIMTANI | Masuk</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body class="auth-body">
  <div class="auth-page">
    <section class="auth-card" aria-labelledby="auth-title">
      <header class="auth-brand">
        <img class="auth-logo" src="{{ asset('images/Logo.png') }}" alt="Logo SIMTANI">
      </header>
      <p class="auth-sub">Kelola kegiatan kelompok tani, jadwal, presensi, dan pembayaran upah dalam satu tempat.</p>

      @if ($errors->any())
        <div class="auth-error" role="alert">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('login.submit') }}">
        @csrf
        <div class="auth-field">
          <label class="auth-sr" for="login">Email atau nomor anggota</label>
          <input id="login" name="login" type="text" value="{{ old('login') }}" placeholder="Masukkan email atau nomor anggota" required autofocus autocomplete="username">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        </div>
        <div class="auth-field">
          <label class="auth-sr" for="password">Kata sandi</label>
          <input id="password" name="password" type="password" placeholder="Kata sandi" required autocomplete="current-password">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
        </div>
        <label class="auth-check">
          <input type="checkbox" id="remember" name="remember" value="1">
          <span>Ingat saya</span>
        </label>
        <button class="auth-submit" type="submit">Masuk</button>
      </form>
    </section>
  </div>
</body>
</html>
