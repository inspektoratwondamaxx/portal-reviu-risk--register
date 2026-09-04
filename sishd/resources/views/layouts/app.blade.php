<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — SISHD</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div class="d-flex">
        <nav class="sidebar text-white flex-shrink-0" style="width: 250px;">
            <div class="p-3 border-bottom border-secondary border-opacity-25">
                <a href="{{ route('dashboard') }}" class="text-white text-decoration-none d-flex align-items-center gap-2">
                    <i class="bi bi-bank2 fs-4"></i>
                    <div>
                        <div class="fw-bold">SISHD</div>
                        <small class="text-white-50" style="font-size: .7rem;">SSH · SBU · HSPK · ASB</small>
                    </div>
                </a>
            </div>
            <div class="nav flex-column p-2" style="font-size: .9rem;">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>

                @role('super_admin', 'admin_ssh')
                    <div class="nav-header">Master Data</div>
                    <a href="{{ route('admin.ssh.index') }}" class="nav-link {{ request()->routeIs('admin.ssh.*') ? 'active' : '' }}"><i class="bi bi-box-seam me-2"></i>SSH</a>
                    <a href="{{ route('admin.sbu.index') }}" class="nav-link {{ request()->routeIs('admin.sbu.*') ? 'active' : '' }}"><i class="bi bi-cash-coin me-2"></i>SBU</a>
                    <a href="{{ route('admin.kategori.index') }}" class="nav-link {{ request()->routeIs('admin.kategori.*') ? 'active' : '' }}"><i class="bi bi-tags me-2"></i>Kategori</a>
                    <a href="{{ route('admin.kelompok-barang.index') }}" class="nav-link {{ request()->routeIs('admin.kelompok-barang.*') ? 'active' : '' }}"><i class="bi bi-boxes me-2"></i>Kelompok Barang</a>
                    <a href="{{ route('admin.kode-aset.index') }}" class="nav-link {{ request()->routeIs('admin.kode-aset.*') ? 'active' : '' }}"><i class="bi bi-upc-scan me-2"></i>Kode Aset</a>
                    <a href="{{ route('admin.kode-rekening.index') }}" class="nav-link {{ request()->routeIs('admin.kode-rekening.*') ? 'active' : '' }}"><i class="bi bi-journal-text me-2"></i>Kode Rekening</a>
                    <a href="{{ route('admin.kode-sipd.index') }}" class="nav-link {{ request()->routeIs('admin.kode-sipd.*') ? 'active' : '' }}"><i class="bi bi-diagram-3 me-2"></i>Kode SIPD</a>

                    <div class="nav-header">Mapping</div>
                    <a href="{{ route('admin.mapping.index') }}" class="nav-link {{ request()->routeIs('admin.mapping.*') ? 'active' : '' }}"><i class="bi bi-signpost-split me-2"></i>Mapping Kode</a>
                @endrole

                @role('super_admin', 'admin_hspk_asb')
                    <div class="nav-header">Analisis</div>
                    <a href="{{ route('admin.hspk.index') }}" class="nav-link {{ request()->routeIs('admin.hspk.*') ? 'active' : '' }}"><i class="bi bi-bricks me-2"></i>HSPK</a>
                    <a href="{{ route('admin.asb.index') }}" class="nav-link {{ request()->routeIs('admin.asb.*') ? 'active' : '' }}"><i class="bi bi-calculator me-2"></i>ASB</a>
                @endrole

                @role('super_admin', 'opd_operator')
                    <div class="nav-header">Usulan OPD</div>
                    <a href="{{ route('opd.usulan.index') }}" class="nav-link {{ request()->routeIs('opd.usulan.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-plus me-2"></i>Usulan Saya</a>
                @endrole

                @role('super_admin', 'verifikator', 'tim_standar_harga', 'pejabat_berwenang')
                    <div class="nav-header">Verifikasi</div>
                    <a href="{{ route('verifikasi.index') }}" class="nav-link {{ request()->routeIs('verifikasi.*') ? 'active' : '' }}">
                        <i class="bi bi-check2-square me-2"></i>Verifikasi Usulan
                        @if($menungguVerifikasiCount ?? 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $menungguVerifikasiCount }}</span>
                        @endif
                    </a>
                @endrole

                @role('super_admin', 'admin_ssh', 'opd_operator')
                    <div class="nav-header">Survei Harga</div>
                    <a href="{{ route('survei-harga.index') }}" class="nav-link {{ request()->routeIs('survei-harga.*') ? 'active' : '' }}"><i class="bi bi-geo-alt me-2"></i>Survei Harga</a>
                @endrole

                @role('super_admin', 'admin_ssh')
                    <div class="nav-header">Import / Export</div>
                    <a href="{{ route('import-export.index') }}" class="nav-link {{ request()->routeIs('import-export.*') ? 'active' : '' }}"><i class="bi bi-arrow-down-up me-2"></i>Import / Export</a>
                @endrole

                @role('super_admin', 'admin_ssh', 'admin_hspk_asb', 'verifikator', 'tim_standar_harga', 'pejabat_berwenang')
                    <div class="nav-header">Laporan</div>
                    <a href="{{ route('laporan.perubahan-harga') }}" class="nav-link {{ request()->routeIs('laporan.*') ? 'active' : '' }}"><i class="bi bi-graph-up-arrow me-2"></i>Laporan</a>
                @endrole

                @role('super_admin')
                    <div class="nav-header">Sistem</div>
                    <a href="{{ route('sistem.users.index') }}" class="nav-link {{ request()->routeIs('sistem.users.*') ? 'active' : '' }}"><i class="bi bi-people me-2"></i>User</a>
                    <a href="{{ route('sistem.opd.index') }}" class="nav-link {{ request()->routeIs('sistem.opd.*') ? 'active' : '' }}"><i class="bi bi-diagram-2 me-2"></i>OPD</a>
                    <a href="{{ route('sistem.tahun-anggaran.index') }}" class="nav-link {{ request()->routeIs('sistem.tahun-anggaran.*') ? 'active' : '' }}"><i class="bi bi-calendar-range me-2"></i>Tahun Anggaran</a>
                    <a href="{{ route('sistem.audit-log.index') }}" class="nav-link {{ request()->routeIs('sistem.audit-log.*') ? 'active' : '' }}"><i class="bi bi-clock-history me-2"></i>Audit Log</a>
                @endrole

                <hr class="text-white-50">
                <a href="{{ route('publik.beranda') }}" class="nav-link"><i class="bi bi-globe2 me-2"></i>Lihat Situs Publik</a>
            </div>
        </nav>

        <div class="flex-grow-1" style="min-width: 0;">
            <nav class="navbar navbar-expand navbar-light bg-white border-bottom px-3 sticky-top">
                <span class="navbar-brand mb-0 h5">@yield('title', 'Dashboard')</span>
                <div class="ms-auto d-flex align-items-center gap-3">
                    @if($tahunAktifGlobal ?? null)
                        <span class="badge text-bg-light border">Tahun Anggaran {{ $tahunAktifGlobal->tahun }}</span>
                    @endif
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span>
                                {{ auth()->user()->name }}
                                <small class="d-block text-muted">{{ auth()->user()->role?->label }}</small>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item" type="submit">Keluar</button></form></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="p-4">
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
