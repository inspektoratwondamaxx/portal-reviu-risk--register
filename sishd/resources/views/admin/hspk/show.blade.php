@extends('layouts.app')

@section('title', 'Detail HSPK')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="mb-0">{{ $item->uraian }}</h4>
        <small class="text-muted">{{ $item->kode }} · {{ $item->jenis_pekerjaan }} · <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></small>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('admin.hspk.hitung-ulang', $item) }}" method="POST">
            @csrf
            <button class="btn btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Hitung Ulang</button>
        </form>
        <a href="{{ route('admin.hspk.edit', $item) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">Komponen</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr><th>Uraian</th><th>Jenis</th><th class="text-end">Koefisien</th><th>Satuan</th><th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th><th></th></tr>
                        </thead>
                        <tbody>
                        @forelse ($item->components as $c)
                            <tr>
                                <td>{{ $c->label() }}</td>
                                <td><span class="badge text-bg-light border">{{ $c->komponen_type->label() }}</span></td>
                                <td class="text-end">{{ number_format($c->koefisien, 4, ',', '.') }}</td>
                                <td>{{ $c->satuan }}</td>
                                <td class="text-end">Rp {{ number_format($c->harga_satuan, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($c->subtotal, 0, ',', '.') }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.hspk.komponen.destroy', [$item, $c]) }}" method="POST" onsubmit="return confirm('Hapus komponen ini?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Belum ada komponen. Tambahkan material/tenaga kerja/peralatan di bawah.</td></tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="5" class="text-end">TOTAL HSPK / {{ $item->satuan }}</td>
                                <td class="text-end">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <hr>
                <h6>Tambah Komponen</h6>
                <form action="{{ route('admin.hspk.komponen.store', $item) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Jenis</label>
                        <select name="komponen_type" id="komponenType" class="form-select" required>
                            @foreach ($komponenTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4" id="sumberSsh">
                        <label class="form-label small text-muted mb-1">Sumber SSH (material)</label>
                        <select name="ssh_item_id" class="form-select">
                            <option value="">-- Manual --</option>
                            @foreach ($sshOptions as $ssh)
                                <option value="{{ $ssh->id }}" data-satuan="{{ $ssh->satuan }}">{{ $ssh->uraian }} ({{ $ssh->merek }}) — Rp {{ number_format($ssh->harga, 0, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4" id="sumberSbu" style="display:none;">
                        <label class="form-label small text-muted mb-1">Sumber SBU (tenaga/alat)</label>
                        <select name="sbu_item_id" class="form-select">
                            <option value="">-- Manual --</option>
                            @foreach ($sbuOptions as $sbu)
                                <option value="{{ $sbu->id }}" data-satuan="{{ $sbu->satuan }}">{{ $sbu->uraian }} — Rp {{ number_format($sbu->besaran, 0, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Uraian Manual (opsional)</label>
                        <input type="text" name="uraian" class="form-control" placeholder="Jika tidak pilih sumber di atas">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Koefisien</label>
                        <input type="number" step="0.0001" name="koefisien" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Satuan</label>
                        <input type="text" name="satuan" id="satuanKomponen" class="form-control">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-clock-history"></i> Riwayat Hitung Ulang</h6>
                @forelse ($riwayatAnalisis as $a)
                    <div class="border-bottom py-2 small">
                        <div class="d-flex justify-content-between">
                            <span>Rp {{ number_format($a->harga_sebelum, 0, ',', '.') }} → Rp {{ number_format($a->harga_sesudah, 0, ',', '.') }}</span>
                            <span class="{{ $a->harga_sesudah >= $a->harga_sebelum ? 'text-danger' : 'text-success' }}">{{ number_format($a->persentase, 2, ',', '.') }}%</span>
                        </div>
                        <div class="text-muted">{{ $a->pemicu ?: 'Perhitungan manual' }} — {{ $a->dihitung_pada->format('d/m/Y H:i') }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Belum ada riwayat perhitungan.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const typeSelect = document.getElementById('komponenType');
    const sshBox = document.getElementById('sumberSsh');
    const sbuBox = document.getElementById('sumberSbu');

    function toggle() {
        const isMaterial = typeSelect.value === 'material';
        sshBox.style.display = isMaterial ? '' : 'none';
        sbuBox.style.display = isMaterial ? 'none' : '';
    }
    typeSelect?.addEventListener('change', toggle);
    toggle();

    document.querySelectorAll('#sumberSsh select, #sumberSbu select').forEach(sel => {
        sel.addEventListener('change', (e) => {
            const opt = e.target.selectedOptions[0];
            document.getElementById('satuanKomponen').value = opt?.dataset?.satuan || '';
        });
    });
})();
</script>
@endpush
