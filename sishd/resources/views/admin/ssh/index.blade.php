@extends('layouts.app')

@section('title', 'Master SSH')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Data Standar Harga Satuan (SSH)</h4>
        <small class="text-muted">Dashboard / Master Data / SSH</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('import-export.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-down-up"></i> Import/Export</a>
        <a href="{{ route('admin.ssh.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah SSH</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Cari uraian, kode, atau merek</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="mis. semen, besi beton...">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1">Kategori</label>
                <select name="kategori" class="form-select">
                    <option value="">Semua</option>
                    @foreach ($kategoriList as $k)
                        <option value="{{ $k->id }}" {{ (string) request('kategori') === (string) $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1">Tahun</label>
                <select name="tahun" class="form-select">
                    <option value="">Semua</option>
                    @foreach ($tahunList as $t)
                        <option value="{{ $t->tahun }}" {{ (string) request('tahun') === (string) $t->tahun ? 'selected' : '' }}>{{ $t->tahun }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">Aktif &amp; proses</option>
                    @foreach ($statusOptions as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead>
                    <tr>
                        <th>Kode</th><th>Uraian</th><th>Spesifikasi</th><th>Merek</th><th>Satuan</th>
                        <th class="text-end">Harga (Rp)</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->kode_barang }}</td>
                            <td>{{ $item->uraian }}</td>
                            <td class="text-muted small">{{ \Illuminate\Support\Str::limit($item->spesifikasi, 40) }}</td>
                            <td>{{ $item->merek }}</td>
                            <td>{{ $item->satuan }}</td>
                            <td class="text-end">{{ number_format($item->harga, 0, ',', '.') }}</td>
                            <td><span class="badge badge-status {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.ssh.show', $item) }}" class="btn btn-sm btn-outline-secondary" title="Detail"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.ssh.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Ubah"><i class="bi bi-pencil"></i></a>
                                @if ($item->is_active)
                                    <form action="{{ route('admin.ssh.nonaktifkan', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Nonaktifkan data ini? Riwayat harga tetap tersimpan.');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" title="Nonaktifkan"><i class="bi bi-slash-circle"></i></button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.ssh.aktifkan', $item) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" title="Aktifkan"><i class="bi bi-check-circle"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data SSH.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>
@endsection
