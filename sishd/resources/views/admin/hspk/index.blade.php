@extends('layouts.app')

@section('title', 'HSPK')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Harga Satuan Pokok Kegiatan (HSPK)</h4>
        <small class="text-muted">Dashboard / Analisis / HSPK</small>
    </div>
    <a href="{{ route('admin.hspk.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah HSPK</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Cari uraian pekerjaan</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control">
            </div>
            <div class="col-md-3">
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
                <thead><tr><th>Kode</th><th>Uraian Pekerjaan</th><th>Satuan</th><th class="text-end">Harga Satuan</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->kode }}</td>
                        <td>{{ $item->uraian }} <span class="text-muted small">({{ $item->jenis_pekerjaan }})</span></td>
                        <td>{{ $item->satuan }}</td>
                        <td class="text-end fw-semibold">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td><span class="badge badge-status {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.hspk.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Kelola Komponen</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data HSPK.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>
@endsection
