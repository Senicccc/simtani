<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIMTANI')</title>
</head>
<body>
    <header>
        <h1>SIMTANI</h1>
        @auth
            <p>{{ auth()->user()->nama_lengkap }} ({{ auth()->user()->role }})</p>
            <nav>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a> |
                    <a href="{{ route('admin.anggota.index') }}">Anggota</a> |
                    <a href="{{ route('admin.jenis-pekerjaan.index') }}">Kategori pekerjaan</a> |
                    <a href="{{ route('admin.jadwal.index') }}">Jadwal</a> |
                    <a href="{{ route('admin.penugasan.index') }}">Penugasan</a> |
                    <a href="{{ route('admin.presensi.index') }}">Presensi</a> |
                    <a href="{{ route('admin.upah.index') }}">Upah</a> |
                    <a href="{{ route('admin.laporan.index') }}">Laporan</a>
                @else
                    <a href="{{ route('anggota.dashboard') }}">Dashboard</a> |
                    <a href="{{ route('anggota.tugas.index') }}">Tugas</a> |
                    <a href="{{ route('anggota.presensi.index') }}">Presensi</a> |
                    <a href="{{ route('anggota.upah.index') }}">Upah</a> |
                    <a href="{{ route('anggota.hubungi-admin') }}">Hubungi admin</a>
                @endif
            </nav>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        @endauth
    </header>

    @if(session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif
    @if($errors->any())
        <div role="alert">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <main>
        @yield('content')
    </main>
</body>
</html>
