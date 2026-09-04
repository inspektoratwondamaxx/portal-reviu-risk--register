@extends('layouts.app')

@section('title', 'Verifikasi Usulan')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">Verifikasi Usulan OPD</h4>
    <small class="text-muted">Dashboard / Verifikasi Usulan</small>
</div>

<ul class="nav nav-pills mb-3">
    @foreach (['menunggu_verifikasi' => 'Menunggu', 'disetujui' => 'Disetujui', 'revisi' => 'Revisi', 'ditolak' => 'Ditolak'] as $val => $label)
        <li class="nav-item">
            <a class="nav-link {{ $statusAktif === $val ? 'active' : '' }}" href="{{ route('verifikasi.index', ['status' => $val]) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>No. Usulan</th><th>Jenis</th><th>OPD</th><th>Uraian</th>@if($statusAktif === 'menunggu_verifikasi')<th>Tahap</th>@endif<th>Tanggal Usulan</th><th></th></tr></thead>
                <tbody>
                @forelse ($proposals as $p)
                    <tr>
                        <td>{{ $p->nomor_usulan }}</td>
                        <td><span class="badge text-bg-light border">{{ strtoupper($p->jenis_usulan) }}</span></td>
                        <td>{{ $p->opd?->singkatan ?: $p->opd?->nama }}</td>
                        <td>{{ $p->items->first()?->data_usulan['uraian'] ?? $p->items->first()?->data_usulan['nama_kegiatan'] ?? '-' }}</td>
                        @if($statusAktif === 'menunggu_verifikasi')
                            <td><span class="badge text-bg-warning text-dark">{{ $p->tahapanKe() }}/{{ count(\App\Models\Proposal::TAHAPAN_URUTAN) }} · {{ \App\Models\ProposalReview::TAHAPAN[$p->tahapan_saat_ini] ?? $p->tahapan_saat_ini }}</span></td>
                        @endif
                        <td>{{ $p->diajukan_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-end"><a href="{{ route('verifikasi.show', $p) }}" class="btn btn-sm btn-primary"><i class="bi bi-check2-square"></i> Verifikasi</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada usulan pada status ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $proposals->links() }}
    </div>
</div>
@endsection
