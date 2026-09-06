@extends('layouts.app')

@section('title', 'Import / Export')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">Import / Export Data</h4>
    <small class="text-muted">Dashboard / Import / Export</small>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-file-earmark-arrow-up"></i> Import Excel</h6>
                <p class="small text-muted">Unduh template terlebih dahulu agar kolom sesuai. Baris yang mirip data lama tetap diimpor namun ditandai sebagai peringatan.</p>
                <div class="d-flex gap-2 mb-3">
                    <a href="{{ route('import-export.template', 'ssh') }}" class="btn btn-sm btn-outline-secondary">Template SSH</a>
                    <a href="{{ route('import-export.template', 'sbu') }}" class="btn btn-sm btn-outline-secondary">Template SBU</a>
                </div>
                <form method="POST" action="{{ route('import-export.import') }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <div class="col-md-4">
                        <select name="jenis" class="form-select" required>
                            <option value="ssh">SSH</option>
                            <option value="sbu">SBU</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary"><i class="bi bi-upload"></i> Import</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Log Import Terakhir</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Jenis</th><th>File</th><th>Sukses</th><th>Gagal</th><th>Status</th><th>Tanggal</th></tr></thead>
                        <tbody>
                        @forelse ($imports as $i)
                            <tr>
                                <td>{{ strtoupper($i->jenis) }}</td>
                                <td class="small">{{ $i->file_name }}</td>
                                <td class="text-success">{{ $i->sukses }}</td>
                                <td class="text-danger">{{ $i->gagal }}</td>
                                <td><span class="badge {{ $i->status === 'selesai' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $i->status }}</span></td>
                                <td class="small">{{ $i->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada aktivitas import.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-file-earmark-arrow-down"></i> Export Excel</h6>
                <form method="POST" action="{{ route('import-export.export') }}" class="row g-2">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Jenis</label>
                        <select name="jenis" class="form-select" required>
                            <option value="ssh">SSH</option>
                            <option value="sbu">SBU</option>
                            <option value="hspk">HSPK</option>
                            <option value="asb">ASB</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Tahun Anggaran</label>
                        <select name="tahun_anggaran_id" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($tahunList as $t)
                                <option value="{{ $t->id }}">{{ $t->tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Kode Aset</label>
                        <select name="asset_code_id" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($assetCodes as $ac)
                                <option value="{{ $ac->id }}">{{ $ac->kode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn-primary"><i class="bi bi-download"></i> Export Excel</button>
                    </div>
                </form>

                <hr>
                <h6 class="card-title"><i class="bi bi-diagram-3"></i> Export Format SIPD</h6>
                <form method="POST" action="{{ route('import-export.export-sipd') }}" class="row g-2">
                    @csrf
                    <div class="col-md-6">
                        <select name="jenis" class="form-select" required>
                            <option value="ssh">SSH</option>
                            <option value="sbu">SBU</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select name="tahun_anggaran_id" class="form-select">
                            <option value="">Semua Tahun</option>
                            @foreach ($tahunList as $t)
                                <option value="{{ $t->id }}">{{ $t->tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn-outline-primary"><i class="bi bi-download"></i> Export Format SIPD</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Log Export Terakhir</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Jenis</th><th>Format</th><th>Baris</th><th>Tanggal</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($exports as $e)
                            <tr>
                                <td>{{ strtoupper($e->jenis) }}</td>
                                <td>{{ strtoupper($e->format) }}</td>
                                <td>{{ $e->total_baris }}</td>
                                <td class="small">{{ $e->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end"><a href="{{ route('import-export.download', $e) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada aktivitas export.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
