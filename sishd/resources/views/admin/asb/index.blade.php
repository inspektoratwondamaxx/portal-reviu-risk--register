@extends('layouts.app')

@section('title', 'ASB')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Analisis Standar Belanja (ASB)</h4>
        <small class="text-muted">Dashboard / Analisis / ASB</small>
    </div>
    <a href="{{ route('admin.asb.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah ASB</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Cari nama kegiatan</label>
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
                <thead><tr><th>Kode</th><th>Nama Kegiatan</th><th>Kelompok</th><th class="text-end">Hasil Perhitungan</th><th>Kewajaran</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->kode }}</td>
                        <td>{{ $item->nama_kegiatan }}</td>
                        <td>{{ $item->kelompok_kegiatan }}</td>
                        <td class="text-end fw-semibold">Rp {{ number_format($item->hasil_perhitungan, 0, ',', '.') }}</td>
                        <td>
                            @php $wajar = $item->isWajar(); @endphp
                            @if (is_null($wajar))
                                <span class="text-muted small">-</span>
                            @elseif ($wajar)
                                <span class="badge text-bg-success">Wajar</span>
                            @else
                                <span class="badge text-bg-danger">Di luar batas</span>
                            @endif
                        </td>
                        <td><span class="badge badge-status {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.asb.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Kelola</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data ASB.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>
@endsection
