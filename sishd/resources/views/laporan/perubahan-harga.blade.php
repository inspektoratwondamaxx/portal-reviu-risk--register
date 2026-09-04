@extends('layouts.app')

@section('title', 'Laporan Perubahan Harga')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">Laporan Perubahan Harga</h4>
    <small class="text-muted">Dashboard / Laporan / Perubahan Harga</small>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Jenis</label>
                <select name="jenis" class="form-select">
                    <option value="">Semua</option>
                    @foreach (['ssh' => 'SSH', 'sbu' => 'SBU', 'hspk' => 'HSPK', 'asb' => 'ASB'] as $val => $label)
                        <option value="{{ $val }}" {{ request('jenis') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-outline-primary"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Item</th><th>Jenis</th><th class="text-end">Harga Lama</th><th class="text-end">Harga Baru</th><th class="text-end">Persentase</th><th>Dasar</th><th>Tanggal</th><th>Oleh</th></tr></thead>
                <tbody>
                @forelse ($histories as $h)
                    @php $item = $h->item(); @endphp
                    <tr>
                        <td>{{ $item?->uraian ?? $item?->nama_kegiatan ?? '—' }}</td>
                        <td><span class="badge text-bg-secondary">{{ $h->itemLabel() }}</span></td>
                        <td class="text-end">Rp {{ number_format($h->harga_lama, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($h->harga_baru, 0, ',', '.') }}</td>
                        <td class="text-end {{ $h->harga_baru >= $h->harga_lama ? 'text-danger' : 'text-success' }}">{{ number_format($h->persentase, 2, ',', '.') }}%</td>
                        <td>{{ $h->dasar_perubahan ?: '-' }}</td>
                        <td>{{ $h->tanggal->format('d/m/Y') }}</td>
                        <td>{{ $h->user?->name ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada perubahan harga pada rentang ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $histories->links() }}
    </div>
</div>
@endsection
