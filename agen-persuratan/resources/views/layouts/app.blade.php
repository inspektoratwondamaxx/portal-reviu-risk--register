<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('judul', 'Agen Persuratan')</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="top"><div class="bar">
  <a class="brand" href="{{ route('dashboard') }}">📬 Agen Persuratan</a>
  <nav>
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'aktif' : '' }}">Beranda</a>
    <a href="{{ route('surat-masuk.index') }}" class="{{ request()->routeIs('surat-masuk.*') ? 'aktif' : '' }}">Surat Masuk</a>
    @if(auth()->user()->bolehLihatSemua())
      <a href="{{ route('surat-keluar.index') }}" class="{{ request()->routeIs('surat-keluar.*') ? 'aktif' : '' }}">Surat Keluar</a>
    @endif
    <a href="{{ route('cari') }}" class="{{ request()->routeIs('cari') ? 'aktif' : '' }}">Cari</a>
  </nav>
  <form method="POST" action="{{ route('logout') }}">@csrf
    <span class="mut">{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
    <button class="btn sek kecil">Keluar</button>
  </form>
</div></header>
<main>
  @if(session('sukses'))<div class="alert ok">{{ session('sukses') }}</div>@endif
  @if(session('duplikat'))<div class="alert warn">{{ session('duplikat') }}</div>@endif
  @yield('isi')
</main>
</body>
</html>
