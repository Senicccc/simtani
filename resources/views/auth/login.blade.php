<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIMTANI | Masuk</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
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
          <button class="auth-password-toggle" type="button" aria-label="Tampilkan kata sandi" aria-controls="password" aria-pressed="false">
            <svg class="auth-eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="auth-eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a14.8 14.8 0 0 1-3.1 3.8"/><path d="M6.2 6.2C3.6 8 2 12 2 12s3.6 7 10 7a10.7 10.7 0 0 0 4.1-.8"/></svg>
          </button>
        </div>
        <label class="auth-check">
          <input type="checkbox" id="remember" name="remember" value="1">
          <span>Ingat saya</span>
        </label>
        <button class="auth-submit" type="submit">Masuk</button>
      </form>
    </section>
  </div>
  <script>
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.querySelector('.auth-password-toggle');

    passwordToggle.addEventListener('click', () => {
      const isVisible = passwordInput.type === 'text';
      passwordInput.type = isVisible ? 'password' : 'text';
      passwordToggle.setAttribute('aria-pressed', String(!isVisible));
      passwordToggle.setAttribute('aria-label', isVisible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
    });
  </script>
</body>
</html>
