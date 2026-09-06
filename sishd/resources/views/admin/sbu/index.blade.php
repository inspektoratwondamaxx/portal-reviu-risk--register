@extends('layouts.app')

@section('title', 'Master SBU')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Data Standar Biaya Umum (SBU)</h4>
        <small class="text-muted">Dashboard / Master Data / SBU</small>
    </div>
    <a href="{{ route('admin.sbu.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah SBU</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Cari uraian</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1">Kategori</label>
                <select name="kategori" class="form-select">
                    <option value="">Semua</option>
                    @foreach ($kategoriOptions as $val => $label)
                        <option value="{{ $val }}" {{ request('kategori') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">Aktif &amp; proses</option>
                    @foreach ($statusOptions as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Kode</th><th>Kategori</th><th>Uraian</th><th>Satuan</th><th class="text-end">Besaran (Rp)</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->kode }}</td>
                        <td><span class="badge text-bg-light border">{{ \App\Models\SbuItem::KATEGORI[$item->kategori] ?? $item->kategori }}</span></td>
                        <td>{{ $item->uraian }}</td>
                        <td>{{ $item->satuan }}</td>
                        <td class="text-end">{{ number_format($item->besaran, 0, ',', '.') }}</td>
                        <td><span class="badge badge-status {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.sbu.edit', $item) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            @if ($item->is_active)
                                <form action="{{ route('admin.sbu.nonaktifkan', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Nonaktifkan data ini?');">
                                    @csrf<button class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle"></i></button>
                                </form>
                            @else
                                <form action="{{ route('admin.sbu.aktifkan', $item) }}" method="POST" class="d-inline">
                                    @csrf<button class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data SBU.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>
@endsection
