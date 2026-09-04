@extends('layouts.app')

@section('title', 'Usulan OPD')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Usulan Data OPD</h4>
        <small class="text-muted">Dashboard / Usulan OPD</small>
    </div>
    <a href="{{ route('opd.usulan.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajukan Usulan Baru</a>
</div>

<ul class="nav nav-pills mb-3">
    @foreach (['' => 'Semua', 'menunggu_verifikasi' => 'Menunggu', 'revisi' => 'Revisi', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $val => $label)
        <li class="nav-item">
            <a class="nav-link {{ request('status', '') === $val ? 'active' : '' }}" href="{{ route('opd.usulan.index', ['status' => $val]) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>No. Usulan</th><th>Jenis</th><th>OPD</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($proposals as $p)
                    <tr>
                        <td>{{ $p->nomor_usulan }}</td>
                        <td><span class="badge text-bg-light border">{{ strtoupper($p->jenis_usulan) }}</span></td>
                        <td>{{ $p->opd?->singkatan ?: $p->opd?->nama }}</td>
                        <td>{{ $p->diajukan_at?->format('d/m/Y') }}</td>
                        <td><span class="badge {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span></td>
                        <td class="text-end"><a href="{{ route('opd.usulan.show', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada usulan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $proposals->links() }}
    </div>
</div>
@endsection
