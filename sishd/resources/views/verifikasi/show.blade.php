@extends('layouts.app')

@section('title', 'Verifikasi Usulan')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">Verifikasi Usulan</h4>
    <small class="text-muted">Dashboard / Usulan OPD / Verifikasi</small>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <h5 class="mb-0">Usulan #{{ $proposal->nomor_usulan }}</h5>
            <span class="badge fs-6 {{ $proposal->status->badgeClass() }}">{{ strtoupper(str_replace('_', ' ', $proposal->status->value)) }}</span>
        </div>
        <div class="row mt-3 g-2">
            <div class="col-md-4"><div class="text-muted small">OPD Pengusul</div><div>{{ $proposal->opd?->nama }}</div></div>
            <div class="col-md-4"><div class="text-muted small">Tanggal Usulan</div><div>{{ $proposal->diajukan_at?->format('d/m/Y H:i') }}</div></div>
            <div class="col-md-4"><div class="text-muted small">Jenis</div><div>{{ strtoupper($proposal->jenis_usulan) }} · {{ ucfirst($proposal->tipe_perubahan) }}</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        @foreach ($proposal->items as $item)
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">Data Usulan</h6>
                    <table class="table table-sm mb-0">
                        @foreach (($item->data_usulan ?? []) as $key => $value)
                            @continue(in_array($key, ['tahun_anggaran_id']) || is_null($value) || $value === '')
                            <tr>
                                <th style="width:180px;" class="text-capitalize">{{ str_replace('_', ' ', $key) }}</th>
                                <td>{{ is_numeric($value) && $key === 'harga' ? 'Rp '.number_format($value, 0, ',', '.') : $value }}</td>
                            </tr>
                        @endforeach
                    </table>
                    @if ($item->hasSimilarWarning())
                        <div class="alert alert-warning small mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Item sudah ada dengan data mirip:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($item->kemiripan as $mirip)
                                    <li>{{ $mirip['uraian'] ?? '' }} {{ isset($mirip['merek']) ? '('.$mirip['merek'].')' : '' }} — Rp {{ number_format($mirip['harga'] ?? 0, 0, ',', '.') }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Alasan Usulan</h6>
                <p class="mb-0">{{ $proposal->alasan_usulan }}</p>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Riwayat Verifikasi</h6>
                @forelse ($proposal->reviews as $r)
                    <div class="border-bottom py-2 small">
                        <div class="d-flex justify-content-between"><strong>{{ ucfirst($r->keputusan) }}</strong><span class="text-muted">{{ $r->reviewed_at->format('d/m/Y') }}</span></div>
                        <div>{{ $r->reviewer?->name }}</div>
                        @if ($r->catatan)<div class="text-muted fst-italic">"{{ $r->catatan }}"</div>@endif
                    </div>
                @empty
                    <p class="text-muted small">Belum ada catatan verifikasi.</p>
                @endforelse
            </div>
        </div>

        @if ($proposal->status->value === 'menunggu_verifikasi')
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">Catatan Verifikasi</h6>
                    <form method="POST" action="{{ route('verifikasi.putuskan', $proposal) }}">
                        @csrf
                        <textarea name="catatan" class="form-control mb-3" rows="3" placeholder="Catatan untuk OPD pengusul..."></textarea>

                        <label class="form-label">Keputusan</label>
                        <select name="keputusan" class="form-select mb-3" required>
                            <option value="setuju">Setuju</option>
                            <option value="revisi">Revisi (Perbaikan)</option>
                            <option value="tolak">Tolak</option>
                        </select>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check2"></i> Simpan Keputusan</button>
                            <a href="{{ route('verifikasi.index') }}" class="btn btn-outline-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
