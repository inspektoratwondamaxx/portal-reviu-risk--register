@extends('layouts.app')

@section('title', 'Rekap ' . strtoupper($jenis))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Rekap {{ strtoupper($jenis) }}</h4>
        <small class="text-muted">Dashboard / Laporan / Rekap {{ strtoupper($jenis) }}</small>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    @foreach (['ssh', 'sbu', 'hspk', 'asb'] as $j)
        <li class="nav-item"><a class="nav-link {{ $jenis === $j ? 'active' : '' }}" href="{{ route('laporan.rekap', $j) }}">{{ strtoupper($j) }}</a></li>
    @endforeach
</ul>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="tahun" class="form-select">
                    <option value="">Semua Tahun</option>
                    @foreach ($tahunList as $t)
                        <option value="{{ $t->tahun }}" {{ (string) request('tahun') === (string) $t->tahun ? 'selected' : '' }}>{{ $t->tahun }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd">
                <thead><tr><th>Kelompok</th><th class="text-end">Jumlah Data</th><th class="text-end">Total Nilai (Rp)</th></tr></thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->category->nama ?? $row->kelompok ?? '(Tidak Berkategori)' }}</td>
                        <td class="text-end">{{ number_format($row->jumlah) }}</td>
                        <td class="text-end">Rp {{ number_format($row->total ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
