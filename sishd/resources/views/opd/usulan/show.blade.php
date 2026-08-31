@extends('layouts.app')

@section('title', 'Detail Usulan')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="mb-0">Usulan {{ $proposal->nomor_usulan }}</h4>
        <small class="text-muted">{{ $proposal->opd?->nama }} · {{ $proposal->diajukan_at?->format('d/m/Y H:i') }}</small>
    </div>
    <span class="badge fs-6 {{ $proposal->status->badgeClass() }}">{{ $proposal->status->label() }}</span>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        @foreach ($proposal->items as $item)
            <div class="card mb-3">
                <div class="card-body">
                    <span class="badge text-bg-light border mb-2">{{ strtoupper($item->item_type) }} · {{ ucfirst($proposal->tipe_perubahan) }}</span>
                    <table class="table table-sm mb-0">
                        @foreach (($item->data_usulan ?? []) as $key => $value)
                            @continue(in_array($key, ['tahun_anggaran_id']) || is_null($value) || $value === '')
                            <tr>
                                <th style="width:200px;" class="text-capitalize">{{ str_replace('_', ' ', $key) }}</th>
                                <td>{{ is_numeric($value) && $key === 'harga' ? 'Rp '.number_format($value, 0, ',', '.') : $value }}</td>
                            </tr>
                        @endforeach
                    </table>

                    @if ($item->hasSimilarWarning())
                        <div class="alert alert-warning small mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Data serupa terdeteksi saat pengajuan:</strong>
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

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-clock-history"></i> Riwayat Verifikasi</h6>
                @forelse ($proposal->reviews as $r)
                    <div class="border-bottom py-2 small">
                        <div class="d-flex justify-content-between">
                            <strong>{{ ucfirst($r->keputusan) }}</strong>
                            <span class="text-muted">{{ $r->reviewed_at->format('d/m/Y') }}</span>
                        </div>
                        <div>{{ $r->reviewer?->name }} ({{ \App\Models\ProposalReview::TAHAPAN[$r->tahapan] ?? $r->tahapan }})</div>
                        @if ($r->catatan)<div class="text-muted fst-italic">"{{ $r->catatan }}"</div>@endif
                    </div>
                @empty
                    <p class="text-muted small mb-0">Belum ada catatan verifikasi.</p>
                @endforelse

                @if ($proposal->status->value === 'revisi')
                    <form action="{{ route('opd.usulan.ajukan-ulang', $proposal) }}" method="POST" class="mt-3">
                        @csrf
                        <button class="btn btn-primary w-100"><i class="bi bi-arrow-repeat"></i> Ajukan Ulang</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
