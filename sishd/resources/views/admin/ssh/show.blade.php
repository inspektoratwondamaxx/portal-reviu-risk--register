@extends('layouts.app')

@section('title', 'Detail SSH')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="mb-0">{{ $item->uraian }}</h4>
        <small class="text-muted">{{ $item->kode_barang }} · <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></small>
    </div>
    <a href="{{ route('admin.ssh.edit', $item) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Ubah</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">Detail Item</h6>
                <table class="table table-sm">
                    <tr><th style="width:200px;">Spesifikasi</th><td>{{ $item->spesifikasi ?: '-' }}</td></tr>
                    <tr><th>Merek</th><td>{{ $item->merek ?: '-' }}</td></tr>
                    <tr><th>Satuan</th><td>{{ $item->satuan }}</td></tr>
                    <tr><th>Harga</th><td class="fw-bold">Rp {{ number_format($item->harga, 0, ',', '.') }}</td></tr>
                    <tr><th>Kategori</th><td>{{ $item->category?->nama ?: '-' }}</td></tr>
                    <tr><th>Kode Aset</th><td>{{ $item->assetCode?->kode ?: '-' }} {{ $item->assetCode?->nama }}</td></tr>
                    <tr><th>Tahun Anggaran</th><td>{{ $item->tahunAnggaran?->tahun }}</td></tr>
                    <tr><th>Sumber Harga</th><td>{{ $item->sumber_harga ?: '-' }}</td></tr>
                    <tr><th>OPD Pengusul</th><td>{{ $item->opd?->nama ?: 'Admin SSH (langsung)' }}</td></tr>
                    <tr><th>Keterangan</th><td>{{ $item->keterangan ?: '-' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Riwayat Perubahan Harga</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Tanggal</th><th class="text-end">Harga Lama</th><th class="text-end">Harga Baru</th><th class="text-end">Persentase</th><th>Dasar</th><th>Oleh</th></tr></thead>
                        <tbody>
                        @forelse ($riwayat as $h)
                            <tr>
                                <td>{{ $h->tanggal->format('d/m/Y') }}</td>
                                <td class="text-end">Rp {{ number_format($h->harga_lama, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($h->harga_baru, 0, ',', '.') }}</td>
                                <td class="text-end {{ $h->harga_baru >= $h->harga_lama ? 'text-danger' : 'text-success' }}">{{ number_format($h->persentase, 2, ',', '.') }}%</td>
                                <td>{{ $h->dasar_perubahan ?: '-' }}</td>
                                <td>{{ $h->user?->name ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada perubahan harga.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-bricks"></i> Dipakai di HSPK</h6>
                @forelse ($hspkTerkait as $h)
                    <a href="{{ route('admin.hspk.show', $h) }}" class="d-block small border-bottom py-2 text-decoration-none">
                        {{ $h->kode }} — {{ $h->uraian }}
                    </a>
                @empty
                    <p class="text-muted small mb-0">Belum dipakai sebagai komponen HSPK manapun.</p>
                @endforelse
                <div class="form-text mt-2">Jika harga item ini berubah, seluruh HSPK di atas otomatis dihitung ulang.</div>
            </div>
        </div>
    </div>
</div>
@endsection
