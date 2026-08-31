@extends('layouts.app')

@section('title', $item->exists ? 'Ubah SSH' : 'Tambah SSH')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">{{ $item->exists ? 'Ubah Data SSH' : 'Tambah SSH' }}</h4>
    <small class="text-muted">Dashboard / Master Data / SSH / {{ $item->exists ? 'Ubah' : 'Tambah' }}</small>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $item->exists ? route('admin.ssh.update', $item) : route('admin.ssh.store') }}">
                    @csrf
                    @if ($item->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" name="kode_barang" value="{{ old('kode_barang', $item->kode_barang) }}" class="form-control" placeholder="Otomatis jika dikosongkan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kode Aset</label>
                            <select name="asset_code_id" class="form-select">
                                <option value="">-- Tidak ada --</option>
                                @foreach ($assetCodes as $ac)
                                    <option value="{{ $ac->id }}" {{ (int) old('asset_code_id', $item->asset_code_id) === $ac->id ? 'selected' : '' }}>{{ $ac->kode }} — {{ $ac->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="form-select">
                                <option value="">-- Tidak ada --</option>
                                @foreach ($kategoriList as $k)
                                    <option value="{{ $k->id }}" {{ (int) old('category_id', $item->category_id) === $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tahun Anggaran <span class="text-danger">*</span></label>
                            <select name="tahun_anggaran_id" class="form-select" required>
                                @foreach ($tahunList as $t)
                                    <option value="{{ $t->id }}" {{ (int) old('tahun_anggaran_id', $item->tahun_anggaran_id ?? optional($tahunList->firstWhere('is_active', true))->id) === $t->id ? 'selected' : '' }}>{{ $t->tahun }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Uraian <span class="text-danger">*</span></label>
                            <input type="text" id="uraian" name="uraian" value="{{ old('uraian', $item->uraian) }}" class="form-control" required>
                            <div id="hasilSerupa" class="mt-2"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Spesifikasi</label>
                            <textarea name="spesifikasi" class="form-control" rows="2">{{ old('spesifikasi', $item->spesifikasi) }}</textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Merek</label>
                            <input type="text" id="merek" name="merek" value="{{ old('merek', $item->merek) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="satuan" value="{{ old('satuan', $item->satuan) }}" class="form-control" required placeholder="mis. Zak, M3, Buah">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="harga" value="{{ old('harga', $item->harga) }}" class="form-control" required>
                        </div>

                        @if ($item->exists)
                            <div class="col-12">
                                <label class="form-label">Dasar Perubahan Harga</label>
                                <input type="text" name="dasar_perubahan" class="form-control" placeholder="mis. Survei harga Agustus 2026">
                                <div class="form-text">Diisi hanya jika mengubah harga — akan tercatat di riwayat perubahan &amp; memicu hitung ulang HSPK terkait.</div>
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">Sumber Harga</label>
                            <input type="text" name="sumber_harga" value="{{ old('sumber_harga', $item->sumber_harga) }}" class="form-control" placeholder="mis. Survei harga">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="keterangan" value="{{ old('keterangan', $item->keterangan) }}" class="form-control">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                        <a href="{{ route('admin.ssh.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Fitur Anti-Duplikasi</h6>
                <p class="small text-muted mb-0">
                    Sistem otomatis memeriksa kemiripan uraian barang menggunakan <em>similarity matching</em>
                    (PostgreSQL pg_trgm) begitu Anda mengetik, agar data yang sudah ada dengan merek/ukuran berbeda
                    tidak terduplikasi sebagai entri baru.
                </p>
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

        fetch(`{{ route('admin.ssh.cek-serupa') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ uraian: q, merek: merek.value }),
        }).then(r => r.json()).then(data => {
            if (!data.hasil || data.hasil.length === 0) { hasil.innerHTML = ''; return; }
            let html = '<div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle"></i> <strong>Data serupa ditemukan:</strong><ul class="mb-0 mt-1">';
            data.hasil.forEach(r => {
                html += `<li>${r.uraian} ${r.merek ? '(' + r.merek + ')' : ''} — Rp ${Number(r.harga).toLocaleString('id-ID')}${r.skor ? ' · kemiripan ' + r.skor + '%' : ''}</li>`;
            });
            html += '</ul></div>';
            hasil.innerHTML = html;
        });
    }

    uraian?.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(cek, 500); });
})();
</script>
@endpush
