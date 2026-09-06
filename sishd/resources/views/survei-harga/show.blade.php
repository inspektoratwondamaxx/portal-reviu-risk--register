@extends('layouts.app')

@section('title', 'Detail Survei')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">{{ $survey->uraian_barang }}</h4>
    <small class="text-muted">Dashboard / Survei Harga / Detail</small>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <table class="table table-sm">
                    <tr><th style="width:180px;">Penyedia</th><td>{{ $survey->vendor?->nama ?: '-' }}</td></tr>
                    <tr><th>Merek</th><td>{{ $survey->merek ?: '-' }}</td></tr>
                    <tr><th>Spesifikasi</th><td>{{ $survey->spesifikasi ?: '-' }}</td></tr>
                    <tr><th>Lokasi</th><td>{{ $survey->lokasi ?: '-' }}</td></tr>
                    <tr><th>Tanggal Survei</th><td>{{ $survey->tanggal_survei->format('d/m/Y') }}</td></tr>
                    <tr><th>Harga</th><td class="fw-bold">Rp {{ number_format($survey->harga, 0, ',', '.') }}</td></tr>
                    <tr><th>Surveyor</th><td>{{ $survey->surveyor?->name ?: '-' }}</td></tr>
                    <tr><th>Keterangan</th><td>{{ $survey->keterangan ?: '-' }}</td></tr>
                </table>

                @if ($survey->evidence->isNotEmpty())
                    <h6 class="mt-3">Bukti Foto</h6>
                    <div class="row g-2">
                        @foreach ($survey->evidence as $e)
                            <div class="col-4">
                                <a href="{{ $e->url() }}" target="_blank">
                                    <img src="{{ $e->url() }}" class="img-fluid rounded border" alt="{{ $e->jenis_bukti }}">
                                </a>
                                <div class="small text-muted text-center">{{ \App\Models\PriceEvidence::JENIS[$e->jenis_bukti] ?? $e->jenis_bukti }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Rekap Harga Sejenis</h6>
                <table class="table table-sm mb-0">
                    <tr><th>Jumlah Survei</th><td>{{ $rekap['jumlah'] }}</td></tr>
                    <tr><th>Minimum</th><td>Rp {{ number_format($rekap['min'], 0, ',', '.') }}</td></tr>
                    <tr><th>Maksimum</th><td>Rp {{ number_format($rekap['max'], 0, ',', '.') }}</td></tr>
                    <tr><th>Median</th><td>Rp {{ number_format($rekap['median'], 0, ',', '.') }}</td></tr>
                    <tr><th>Rata-rata</th><td>Rp {{ number_format($rekap['rata_rata'], 0, ',', '.') }}</td></tr>
                    <tr class="table-primary"><th>Rekomendasi</th><td class="fw-bold">Rp {{ number_format($rekap['rekomendasi'], 0, ',', '.') }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
