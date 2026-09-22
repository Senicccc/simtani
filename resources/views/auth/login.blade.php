<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIMTANI — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  <div id="login-screen">
    <div class="login-brand">
      <div class="mark"><img src="{{ asset('images/Logo.jpg') }}" alt="Logo SIMTANI"></div>
      <h1>SIMTANI</h1>
      <p>Kelola kelompok tani lebih rapi &amp; adil — jadwal, presensi, dan upah dalam satu sistem.</p>
    </div>
    <div class="login-form">
      <h2>Masuk ke akun</h2>

      @if ($errors->any())
        <div style="color:#b3261e;font-size:12px;margin-bottom:10px">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('login.submit') }}">
        @csrf
        <div class="field">
          <label for="login">Email atau No. anggota</label>
          <input id="login" name="login" type="text" value="{{ old('login') }}" placeholder="Contoh: 2024001" required autofocus>
        </div>
        <div class="field">
          <label for="password">Kata sandi</label>
          <input id="password" name="password" type="password" placeholder="••••••••" required>
        </div>
        <div class="field" style="display:flex;align-items:center;gap:6px">
          <input type="checkbox" id="remember" name="remember" value="1" style="width:auto">
          <label for="remember" style="margin:0;font-size:12px">Ingat saya</label>
        </div>
        <button class="btn-primary" type="submit">
          <span class="btn-label">Masuk</span>
        </button>
      </form>
    </div>
  </div>
</body>
</html>
