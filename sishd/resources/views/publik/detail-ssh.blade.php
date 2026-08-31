@extends('layouts.public')

@section('title', $item->uraian)

@section('content')
<div class="container py-4">
    <a href="{{ url()->previous() }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali</a>

    <div class="card mt-3">
        <div class="card-body">
            <span class="badge text-bg-primary mb-2">SSH · {{ $item->kode_barang }}</span>
            <h3>{{ $item->uraian }}</h3>
            <p class="text-muted mb-3">{{ $item->spesifikasi }}</p>

            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="text-muted small">Merek</div>
                    <div class="fw-semibold">{{ $item->merek ?: '-' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small">Satuan</div>
                    <div class="fw-semibold">{{ $item->satuan }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small">Tahun</div>
                    <div class="fw-semibold">{{ $item->tahunAnggaran?->tahun }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small">Kategori</div>
                    <div class="fw-semibold">{{ $item->category?->nama ?: '-' }}</div>
                </div>
            </div>

            <div class="mt-4 p-3 bg-light rounded">
                <div class="text-muted small">Harga Saat Ini</div>
                <div class="display-6 fw-bold text-primary">Rp {{ number_format($item->harga, 0, ',', '.') }}</div>
                <div class="text-muted small">Sumber: {{ $item->sumber_harga ?: '-' }}</div>
            </div>
        </div>
    </div>

    @if ($riwayat->isNotEmpty())
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Riwayat Perubahan Harga</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Tanggal</th><th class="text-end">Harga Lama</th><th class="text-end">Harga Baru</th><th class="text-end">Persentase</th><th>Dasar</th></tr></thead>
                        <tbody>
                        @foreach ($riwayat as $h)
                            <tr>
                                <td>{{ $h->tanggal->format('d/m/Y') }}</td>
                                <td class="text-end">Rp {{ number_format($h->harga_lama, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($h->harga_baru, 0, ',', '.') }}</td>
                                <td class="text-end {{ $h->harga_baru >= $h->harga_lama ? 'text-danger' : 'text-success' }}">{{ number_format($h->persentase, 2, ',', '.') }}%</td>
                                <td>{{ $h->dasar_perubahan ?: '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
