<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — SISHD</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center" style="min-height:100vh; background: linear-gradient(135deg, #0b2447, #1e4fa3);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="text-center text-white mb-4">
                    <i class="bi bi-bank2 display-4"></i>
                    <h4 class="fw-bold mt-2 mb-0">SISHD</h4>
                    <small>Sistem Informasi Standar Harga Daerah</small>
                </div>
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">Masuk ke Back Office</h5>

                        @include('partials.flash')

                        <form method="POST" action="{{ route('login.attempt') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kata Sandi</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label" for="remember">Ingat saya</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Masuk</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="{{ route('publik.beranda') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke situs publik</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
