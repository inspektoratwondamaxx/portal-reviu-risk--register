@extends('layouts.app')

@section('title', 'Detail ASB')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="mb-0">{{ $item->nama_kegiatan }}</h4>
        <small class="text-muted">{{ $item->kode }} · {{ $item->kelompok_kegiatan }} · <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></small>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('admin.asb.hitung-ulang', $item) }}" method="POST">
            @csrf
            <button class="btn btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Hitung Ulang</button>
        </form>
        <a href="{{ route('admin.asb.edit', $item) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">Variabel Kegiatan</h6>
                <div class="table-responsive mb-3">
                    <table class="table align-middle">
                        <thead class="table-light"><tr><th>Kode</th><th>Label</th><th class="text-end">Nilai</th><th>Satuan</th><th>Sumber</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($item->variables as $v)
                            <tr>
                                <td><code>{{ '{'.$v->kode_variabel.'}' }}</code></td>
                                <td>{{ $v->label }}</td>
                                <td class="text-end">{{ number_format($v->nilai, 2, ',', '.') }}</td>
                                <td>{{ $v->satuan }}</td>
                                <td><span class="badge text-bg-light border">{{ \App\Models\AsbVariable::SUMBER[$v->sumber_tipe] ?? $v->sumber_tipe }}</span></td>
                                <td class="text-end">
                                    <form action="{{ route('admin.asb.variabel.destroy', [$item, $v]) }}" method="POST" onsubmit="return confirm('Hapus variabel ini?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada variabel.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <form action="{{ route('admin.asb.variabel.store', $item) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Kode Variabel</label>
                        <input type="text" name="kode_variabel" class="form-control" placeholder="luas_bangunan" pattern="[a-zA-Z_][a-zA-Z0-9_]*" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Label</label>
                        <input type="text" name="label" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Sumber</label>
                        <select name="sumber_tipe" id="sumberTipe" class="form-select">
                            @foreach (\App\Models\AsbVariable::SUMBER as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2" id="sumberIdBox" style="display:none;">
                        <label class="form-label small text-muted mb-1">Pilih Sumber</label>
                        <select name="sumber_id" id="sumberId" class="form-select"></select>
                    </div>
                    <div class="col-md-2" id="nilaiBox">
                        <label class="form-label small text-muted mb-1">Nilai</label>
                        <input type="number" step="0.0001" name="nilai" id="nilaiInput" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted mb-1">Satuan</label>
                        <input type="text" name="satuan" class="form-control">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-primary"><i class="bi bi-plus-lg"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Formula (Parameterized)</h6>
                <form action="{{ route('admin.asb.formula.store', $item) }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-12">
                        <input type="text" name="ekspresi" class="form-control font-monospace" value="{{ $item->formula?->ekspresi }}" placeholder="{luas_bangunan} * {standar_biaya_per_m2}" required>
                        <div class="form-text">Gunakan <code>{kode_variabel}</code>, operator <code>+ - * /</code>, dan tanda kurung. Tidak memakai eval PHP — diproses parser aritmatika internal yang aman.</div>
                    </div>
                    <div class="col-12">
                        <input type="text" name="keterangan" class="form-control" value="{{ $item->formula?->keterangan }}" placeholder="Keterangan formula (opsional)">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan Formula &amp; Hitung</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="text-muted small">Hasil Perhitungan</div>
                <div class="display-6 fw-bold text-primary">Rp {{ number_format($item->hasil_perhitungan, 0, ',', '.') }}</div>

                @php $wajar = $item->isWajar(); @endphp
                @if (! is_null($wajar))
                    <div class="mt-2">
                        @if ($wajar)
                            <span class="badge text-bg-success">Dalam batas kewajaran</span>
                        @else
                            <span class="badge text-bg-danger">Di luar batas kewajaran</span>
                        @endif
                    </div>
                    <div class="text-muted small mt-2">
                        Batas: Rp {{ number_format($item->batas_minimal ?? 0, 0, ',', '.') }} — Rp {{ number_format($item->batas_maksimal ?? 0, 0, ',', '.') }}
                    </div>
                @endif

                @if ($item->catatan)
                    <div class="alert alert-warning small mt-3 mb-0 text-start">{{ $item->catatan }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const sumberTipe = document.getElementById('sumberTipe');
    const sumberIdBox = document.getElementById('sumberIdBox');
    const sumberId = document.getElementById('sumberId');
    const nilaiBox = document.getElementById('nilaiBox');

    const options = {
        ssh_item: @json($sshOptions->map->only(['id', 'uraian', 'harga'])->values()),
        hspk: @json($hspkOptions->map->only(['id', 'uraian', 'harga_satuan'])->values()),
        sbu_item: @json($sbuOptions->map->only(['id', 'uraian', 'besaran'])->values()),
    };
    const fmtRp = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');

    function toggle() {
        const tipe = sumberTipe.value;
        if (tipe === 'manual') {
            sumberIdBox.style.display = 'none';
            nilaiBox.style.display = '';
            return;
        }
        sumberIdBox.style.display = '';
        nilaiBox.style.display = 'none';
        sumberId.innerHTML = (options[tipe] || []).map(o => {
            const harga = o.harga ?? o.harga_satuan ?? o.besaran ?? 0;
            return `<option value="${o.id}">${o.uraian} — ${fmtRp(harga)}</option>`;
        }).join('');
    }

    sumberTipe?.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush
