<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') — Sistem Informasi Standar Harga Daerah</title>
    <meta name="description" content="Sistem Informasi Standar Harga Daerah (SISHD) — pencarian SSH, SBU, HSPK, dan ASB yang telah dipublikasikan.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="bg-white border-bottom sticky-top">
        <nav class="navbar navbar-expand-lg container py-2">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('publik.beranda') }}">
                <i class="bi bi-bank2 fs-3 text-primary"></i>
                <span>
                    <div class="fw-bold lh-1">SISHD</div>
                    <small class="text-muted" style="font-size:.68rem;">Sistem Informasi Standar Harga Daerah</small>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPublik">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navPublik">
                <ul class="navbar-nav gap-2">
                    <li class="nav-item"><a class="nav-link" href="{{ route('publik.beranda') }}">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('publik.cari', ['jenis' => 'ssh']) }}">SSH</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('publik.cari', ['jenis' => 'sbu']) }}">SBU</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('publik.cari', ['jenis' => 'hspk']) }}">HSPK</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('publik.cari', ['jenis' => 'asb']) }}">ASB</a></li>
                    <li class="nav-item">
                        @auth
                            <a class="btn btn-primary btn-sm" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                        @else
                            <a class="btn btn-primary btn-sm" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
                        @endauth
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    @include('partials.flash-container')

    <main>
        @yield('content')
    </main>

    <footer class="bg-dark text-white-50 py-4 mt-5">
        <div class="container small d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; {{ now()->year }} Sistem Informasi Standar Harga Daerah (SISHD).</span>
            <span>Data SSH/SBU/HSPK/ASB yang tampil telah melalui proses verifikasi.</span>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
