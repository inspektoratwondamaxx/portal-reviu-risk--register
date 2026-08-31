@extends('layouts.app')

@section('title', 'Ajukan Usulan')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">Usulan Data Baru</h4>
    <small class="text-muted">Dashboard / Usulan OPD / Usulan Baru</small>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('opd.usulan.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis Usulan <span class="text-danger">*</span></label>
                            <select name="jenis_usulan" class="form-select" required>
                                @foreach (['ssh' => 'SSH', 'sbu' => 'SBU', 'hspk' => 'HSPK', 'asb' => 'ASB'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('jenis_usulan', $jenis) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Untuk usulan HSPK/ASB, data awal dibuat lalu dilengkapi komponen/formula oleh Admin HSPK/ASB.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipe Perubahan <span class="text-danger">*</span></label>
                            <select name="tipe_perubahan" class="form-select" required>
                                <option value="baru">Data Baru</option>
                                <option value="perubahan">Perubahan Data Ada</option>
                                <option value="nonaktif">Usulan Nonaktifkan</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Uraian <span class="text-danger">*</span></label>
                            <input type="text" id="uraian" name="uraian" value="{{ old('uraian') }}" class="form-control" required placeholder="mis. Paku Beton 5 cm">
                            <div id="hasilSerupa" class="mt-2"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Spesifikasi</label>
                            <input type="text" name="spesifikasi" value="{{ old('spesifikasi') }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Merek</label>
                            <input type="text" id="merek" name="merek" value="{{ old('merek') }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="satuan" value="{{ old('satuan') }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Harga Usulan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="harga" value="{{ old('harga') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sumber Harga</label>
                            <input type="text" name="sumber_harga" value="{{ old('sumber_harga') }}" class="form-control" placeholder="mis. Survei toko">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alasan Usulan <span class="text-danger">*</span></label>
                            <textarea name="alasan_usulan" class="form-control" rows="2" required>{{ old('alasan_usulan') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="keterangan" value="{{ old('keterangan') }}" class="form-control">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Ajukan Usulan</button>
                        <a href="{{ route('opd.usulan.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Alur Usulan</h6>
                <ol class="small text-muted mb-0">
                    <li>Usulan diajukan → status <em>Menunggu Verifikasi</em>.</li>
                    <li>Verifikator memeriksa: Setuju / Revisi / Tolak.</li>
                    <li>Jika disetujui, data otomatis aktif di Master Data.</li>
                    <li>Jika diminta revisi, Anda dapat mengajukan ulang.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const uraian = document.getElementById('uraian');
    const merek = document.getElementById('merek');
    const hasil = document.getElementById('hasilSerupa');
    let timer;

    function cek() {
        const q = uraian.value.trim();
        if (q.length < 3) { hasil.innerHTML = ''; return; }
        fetch(`{{ route('opd.usulan.cek-serupa') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ uraian: q, merek: merek.value }),
        }).then(r => r.json()).then(data => {
            if (!data.hasil || data.hasil.length === 0) { hasil.innerHTML = ''; return; }
            let html = '<div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle"></i> <strong>Data serupa ditemukan — periksa dulu sebelum mengajukan:</strong><ul class="mb-0 mt-1">';
            data.hasil.forEach(r => { html += `<li>${r.uraian} ${r.merek ? '(' + r.merek + ')' : ''} — Rp ${Number(r.harga).toLocaleString('id-ID')}</li>`; });
            html += '</ul></div>';
            hasil.innerHTML = html;
        });
    }
    uraian?.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(cek, 500); });
})();
</script>
@endpush
