@extends('layouts.app')

@section('title', 'Survei Harga')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Survei Harga</h4>
        <small class="text-muted">Dashboard / Survei Harga</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.penyedia.index') }}" class="btn btn-outline-secondary"><i class="bi bi-shop"></i> Penyedia/Toko</a>
        <a href="{{ route('survei-harga.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Input Survei</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small text-muted mb-1">Cari uraian barang</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="mis. semen 40 kg">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-outline-primary"><i class="bi bi-filter"></i> Cari &amp; Rekap</button>
            </div>
        </form>

        @if ($rekap)
            <div class="row g-2 mt-3 text-center">
                <div class="col"><div class="border rounded p-2"><div class="small text-muted">Minimum</div><div class="fw-bold">Rp {{ number_format($rekap['min'], 0, ',', '.') }}</div></div></div>
                <div class="col"><div class="border rounded p-2"><div class="small text-muted">Maksimum</div><div class="fw-bold">Rp {{ number_format($rekap['max'], 0, ',', '.') }}</div></div></div>
                <div class="col"><div class="border rounded p-2"><div class="small text-muted">Median</div><div class="fw-bold">Rp {{ number_format($rekap['median'], 0, ',', '.') }}</div></div></div>
                <div class="col"><div class="border rounded p-2"><div class="small text-muted">Rata-rata</div><div class="fw-bold">Rp {{ number_format($rekap['rata_rata'], 0, ',', '.') }}</div></div></div>
                <div class="col"><div class="border rounded p-2 bg-primary text-white"><div class="small">Rekomendasi</div><div class="fw-bold">Rp {{ number_format($rekap['rekomendasi'], 0, ',', '.') }}</div></div></div>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Barang</th><th>Penyedia</th><th>Merek</th><th class="text-end">Harga</th><th>Tanggal</th><th>Bukti</th><th></th></tr></thead>
                <tbody>
                @forelse ($surveys as $s)
                    <tr>
                        <td>{{ $s->uraian_barang }}</td>
                        <td>{{ $s->vendor?->nama ?: '-' }}</td>
                        <td>{{ $s->merek }}</td>
                        <td class="text-end">Rp {{ number_format($s->harga, 0, ',', '.') }}</td>
                        <td>{{ $s->tanggal_survei->format('d/m/Y') }}</td>
                        <td>{{ $s->evidence->count() }} file</td>
                        <td class="text-end">
                            <a href="{{ route('survei-harga.show', $s) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <form action="{{ route('survei-harga.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data survei ini?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data survei.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $surveys->links() }}
    </div>
</div>
@endsection
