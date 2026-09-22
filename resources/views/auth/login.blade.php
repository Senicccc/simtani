<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login SIMTANI</title>
</head>
<body>
    <main>
        <h1>Login SIMTANI</h1>
        @if($errors->any())
            <div role="alert">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="post" action="{{ route('login.submit') }}">
            @csrf
            <label for="login">Email atau nomor anggota</label>
            <input id="login" name="login" value="{{ old('login') }}" required autofocus>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
            <label><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            <button type="submit">Masuk</button>
        </form>
    </main>
</body>
</html>
