<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk – Agen Persuratan</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body><main><div class="login card">
<h1>📬 Agen Persuratan</h1><p class="mut">Register surat masuk, surat keluar, dan disposisi.</p>
<form method="POST" action="{{ route('login.proses') }}">@csrf
  <p><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
  @error('email')<div class="err-t">{{ $message }}</div>@enderror</p>
  <p><label for="password">Kata sandi</label><input id="password" type="password" name="password" required></p>
  <p><label style="font-weight:400"><input type="checkbox" name="ingat" value="1"> Ingat saya</label></p>
  <button class="btn" style="width:100%">Masuk</button>
</form></div></main></body></html>
