@extends('layouts.app')

@section('title', 'Mapping Kode')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Mapping Kode Aset → Rekening → SIPD</h4>
        <small class="text-muted">Dashboard / Mapping</small>
    </div>
    <form action="{{ route('admin.mapping.validasi') }}" method="POST">
        @csrf
        <button class="btn btn-outline-primary"><i class="bi bi-check2-all"></i> Validasi Semua</button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title"><i class="bi bi-search"></i> Cek Cepat Kode Aset</h6>
        <div class="input-group" style="max-width: 420px;">
            <input type="text" id="cekKodeInput" class="form-control" placeholder="mis. 1.3.01.01">
            <button class="btn btn-outline-secondary" id="cekKodeBtn">Cek</button>
        </div>
        <div id="cekKodeHasil" class="mt-2"></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title">Tambah Mapping</h6>
        <form action="{{ route('admin.mapping.store') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Kode Aset</label>
                <select name="asset_code_id" class="form-select" required>
                    @foreach ($assetCodes as $ac)
                        <option value="{{ $ac->id }}">{{ $ac->kode }} — {{ $ac->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Kode Rekening</label>
                <select name="account_code_id" class="form-select">
                    <option value="">-- Belum ada --</option>
                    @foreach ($accountCodes as $ac)
                        <option value="{{ $ac->id }}">{{ $ac->kode }} — {{ $ac->uraian }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Kode SIPD</label>
                <select name="sipd_code_id" class="form-select">
                    <option value="">-- Belum ada --</option>
                    @foreach ($sipdCodes as $sc)
                        <option value="{{ $sc->id }}">{{ $sc->kode }} — {{ $sc->uraian }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title">Daftar Mapping</h6>
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Kode Aset</th><th>Kode Rekening</th><th>Kode SIPD</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($mappings as $m)
                    <tr>
                        <td>{{ $m->assetCode?->kode }} — {{ $m->assetCode?->nama }}</td>
                        <td>{{ $m->accountCode?->kode ?: '-' }}</td>
                        <td>{{ $m->sipdCode?->kode ?: '-' }}</td>
                        <td><span class="badge {{ $m->status->badgeClass() }}">{{ $m->status->icon() }} {{ $m->status->label() }}</span></td>
                        <td class="text-end">
                            <form action="{{ route('admin.mapping.destroy', $m) }}" method="POST" onsubmit="return confirm('Hapus mapping ini?');" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada mapping.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $mappings->links() }}
    </div>
</div>

@if ($belumDipetakan->total() > 0)
<div class="card border-warning">
    <div class="card-body">
        <h6 class="card-title text-warning"><i class="bi bi-exclamation-triangle"></i> Kode Aset Belum Dipetakan ({{ $belumDipetakan->total() }})</h6>
        <ul class="mb-0">
            @foreach ($belumDipetakan as $ac)
                <li>{{ $ac->kode }} — {{ $ac->nama }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.getElementById('cekKodeBtn')?.addEventListener('click', () => {
    const kode = document.getElementById('cekKodeInput').value.trim();
    if (!kode) return;
    fetch(`{{ route('admin.mapping.cek-kode') }}?kode=${encodeURIComponent(kode)}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('cekKodeHasil').innerHTML =
                `<div class="alert alert-light border">${data.icon} <strong>${data.label}</strong>${data.asset_code ? ' — ' + data.asset_code.nama : ''}</div>`;
        });
});
</script>
@endpush
