@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
    <section class="text-white py-5" style="background: linear-gradient(135deg, #0b2447, #1e4fa3);">
        <div class="container py-4 text-center">
            <h1 class="fw-bold">Sistem Informasi Standar Harga Daerah</h1>
            <p class="lead">Pencarian data SSH, SBU, HSPK, dan ASB yang telah dipublikasikan secara resmi.</p>

            <div class="card shadow-lg mx-auto mt-4 text-dark" style="max-width: 780px;">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('publik.cari') }}" class="row g-2">
                        <div class="col-12">
                            <label class="form-label small text-muted mb-1">Kata Kunci</label>
                            <input type="text" name="q" class="form-control form-control-lg" placeholder="mis. semen 40 kg">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small text-muted mb-1">Jenis</label>
                            <select name="jenis" class="form-select">
                                <option value="ssh">SSH</option>
                                <option value="sbu">SBU</option>
                                <option value="hspk">HSPK</option>
                                <option value="asb">ASB</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small text-muted mb-1">Kategori</label>
                            <select name="kategori_id" class="form-select">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($kategoriPopuler as $kategori)
                                    <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small text-muted mb-1">Tahun</label>
                            <select name="tahun" class="form-select">
                                @for ($t = now()->year; $t >= now()->year - 3; $t--)
                                    <option value="{{ $t }}" {{ $t === now()->year ? 'selected' : '' }}>{{ $t }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6 col-md-3 d-grid">
                            <label class="form-label small text-muted mb-1 d-none d-md-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-search"></i> Cari Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="row g-3 text-center">
            <div class="col-6 col-lg-3">
                <a href="{{ route('publik.cari', ['jenis' => 'ssh']) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm"><div class="card-body">
                        <i class="bi bi-box-seam fs-1 text-primary"></i>
                        <div class="fs-3 fw-bold mt-2">{{ number_format($ringkasan['ssh']) }}</div>
                        <div class="text-muted">Item SSH</div>
                    </div></div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('publik.cari', ['jenis' => 'sbu']) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm"><div class="card-body">
                        <i class="bi bi-cash-coin fs-1 text-success"></i>
                        <div class="fs-3 fw-bold mt-2">{{ number_format($ringkasan['sbu']) }}</div>
                        <div class="text-muted">Item SBU</div>
                    </div></div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('publik.cari', ['jenis' => 'hspk']) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm"><div class="card-body">
                        <i class="bi bi-bricks fs-1 text-warning"></i>
                        <div class="fs-3 fw-bold mt-2">{{ number_format($ringkasan['hspk']) }}</div>
                        <div class="text-muted">Item HSPK</div>
                    </div></div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('publik.cari', ['jenis' => 'asb']) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm"><div class="card-body">
                        <i class="bi bi-calculator fs-1 text-purple" style="color:#7c3aed;"></i>
                        <div class="fs-3 fw-bold mt-2">{{ number_format($ringkasan['asb']) }}</div>
                        <div class="text-muted">Item ASB</div>
                    </div></div>
                </a>
            </div>
        </div>
    </section>

    <section class="container pb-5">
        <h5 class="mb-3">Tentang SISHD</h5>
        <p class="text-muted">
            SISHD adalah database terpusat standar harga pemerintah daerah yang menjadi <em>single source of truth</em>
            untuk Standar Satuan Harga (SSH), Standar Biaya Umum (SBU), Harga Satuan Pokok Kegiatan (HSPK), dan Analisis
            Standar Belanja (ASB). Setiap usulan data baru dari OPD melalui proses verifikasi sebelum dipublikasikan,
            dan setiap perubahan harga tercatat dalam riwayat yang dapat ditelusuri.
        </p>
    </section>
@endsection
