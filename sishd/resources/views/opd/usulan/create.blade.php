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
                            <select name="jenis_usulan" id="jenisUsulan" class="form-select" required>
                                @foreach (['ssh' => 'SSH', 'sbu' => 'SBU', 'hspk' => 'HSPK', 'asb' => 'ASB'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('jenis_usulan', $jenis) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" data-jenis-hint="hspk,asb">Data awal dibuat lalu dilengkapi komponen/formula oleh Admin HSPK/ASB setelah disetujui.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipe Perubahan <span class="text-danger">*</span></label>
                            <select name="tipe_perubahan" id="tipePerubahan" class="form-select" required>
                                <option value="baru">Data Baru</option>
                                <option value="perubahan">Perubahan Data Ada</option>
                                <option value="nonaktif">Usulan Nonaktifkan</option>
                            </select>
                        </div>

                        <div class="col-12" id="pencarianItemAda" style="display:none;">
                            <label class="form-label">Cari Data yang Diubah/Dinonaktifkan <span class="text-danger">*</span></label>
                            <input type="text" id="cariItemInput" class="form-control" placeholder="Ketik uraian/nama untuk mencari...">
                            <input type="hidden" name="existing_item_id" id="existingItemId">
                            <div id="cariItemHasil" class="list-group mt-1"></div>
                            <div id="cariItemTerpilih" class="form-text text-success"></div>
                        </div>

                        <div class="col-12" data-jenis="ssh,sbu,hspk">
                            <label class="form-label">Uraian <span class="text-danger">*</span></label>
                            <input type="text" id="uraian" name="uraian" value="{{ old('uraian') }}" class="form-control" placeholder="mis. Paku Beton 5 cm" data-req>
                            <div id="hasilSerupa" class="mt-2"></div>
                        </div>
                        <div class="col-12" data-jenis="asb">
                            <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kegiatan" value="{{ old('nama_kegiatan') }}" class="form-control" placeholder="mis. Pembangunan Gedung Pemerintah" data-req>
                        </div>

                        <div class="col-12" data-jenis="ssh">
                            <label class="form-label">Spesifikasi</label>
                            <input type="text" name="spesifikasi" value="{{ old('spesifikasi') }}" class="form-control">
                        </div>
                        <div class="col-md-4" data-jenis="ssh">
                            <label class="form-label">Merek</label>
                            <input type="text" id="merek" name="merek" value="{{ old('merek') }}" class="form-control">
                        </div>
                        <div class="col-md-4" data-jenis="ssh,sbu,hspk">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="satuan" value="{{ old('satuan') }}" class="form-control" data-req>
                        </div>
                        <div class="col-md-4" data-jenis="ssh">
                            <label class="form-label">Harga Usulan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="harga" value="{{ old('harga') }}" class="form-control" data-req>
                        </div>
                        <div class="col-md-6" data-jenis="ssh">
                            <label class="form-label">Sumber Harga</label>
                            <input type="text" name="sumber_harga" value="{{ old('sumber_harga') }}" class="form-control" placeholder="mis. Survei toko">
                        </div>

                        <div class="col-md-4" data-jenis="sbu">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" class="form-select" data-req>
                                @foreach ($kategoriSbu as $val => $label)
                                    <option value="{{ $val }}" {{ old('kategori') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4" data-jenis="sbu">
                            <label class="form-label">Besaran (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="besaran" value="{{ old('besaran') }}" class="form-control" data-req>
                        </div>
                        <div class="col-md-4" data-jenis="sbu">
                            <label class="form-label">Wilayah</label>
                            <input type="text" name="wilayah" value="{{ old('wilayah') }}" class="form-control">
                        </div>
                        <div class="col-12" data-jenis="sbu">
                            <label class="form-label">Dasar Penetapan</label>
                            <input type="text" name="dasar_penetapan" value="{{ old('dasar_penetapan') }}" class="form-control" placeholder="mis. SK Bupati">
                        </div>

                        <div class="col-12" data-jenis="hspk">
                            <label class="form-label">Jenis Pekerjaan</label>
                            <input type="text" name="jenis_pekerjaan" value="{{ old('jenis_pekerjaan') }}" class="form-control" placeholder="mis. Struktur Beton">
                        </div>

                        <div class="col-md-6" data-jenis="asb">
                            <label class="form-label">Kelompok Kegiatan</label>
                            <input type="text" name="kelompok_kegiatan" value="{{ old('kelompok_kegiatan') }}" class="form-control" placeholder="mis. Belanja Modal Gedung">
                        </div>
                        <div class="col-md-6" data-jenis="asb">
                            <label class="form-label">Satuan Variabel Utama</label>
                            <input type="text" name="satuan_variabel" value="{{ old('satuan_variabel') }}" class="form-control" placeholder="mis. M2">
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
    const jenisSelect = document.getElementById('jenisUsulan');
    const tipeSelect = document.getElementById('tipePerubahan');
    const pencarianBox = document.getElementById('pencarianItemAda');
    const cariInput = document.getElementById('cariItemInput');
    const cariHasil = document.getElementById('cariItemHasil');
    const cariTerpilih = document.getElementById('cariItemTerpilih');
    const existingItemId = document.getElementById('existingItemId');

    function currentJenis() {
        return jenisSelect.value;
    }

    function toggleFields() {
        const jenis = currentJenis();
        // Usulan nonaktifkan tidak butuh detail item sama sekali — cukup pilih target lewat pencarian.
        const sembunyikanSemua = tipeSelect.value === 'nonaktif';

        document.querySelectorAll('[data-jenis]').forEach((el) => {
            const shown = !sembunyikanSemua && el.dataset.jenis.split(',').includes(jenis);
            el.style.display = shown ? '' : 'none';
            // input/select di dalam container display:none tetap lolos constraint validation di
            // sebagian browser tapi memblokir submit tanpa pesan di Chromium ("not focusable") kalau
            // masih required — jadi required ditoggle manual lewat marker data-req, bukan diandalkan
            // ke CSS display saja.
            el.querySelectorAll('[data-req]').forEach((field) => { field.required = shown; });
        });
        document.querySelectorAll('[data-jenis-hint]').forEach((el) => {
            el.style.display = (!sembunyikanSemua && el.dataset.jenisHint.split(',').includes(jenis)) ? '' : 'none';
        });
    }

    function toggleExistingPicker() {
        const perlu = ['perubahan', 'nonaktif'].includes(tipeSelect.value);
        pencarianBox.style.display = perlu ? '' : 'none';
        if (!perlu) {
            existingItemId.value = '';
            cariTerpilih.textContent = '';
        }
    }

    let timer;
    function cariItem() {
        const q = cariInput.value.trim();
        cariHasil.innerHTML = '';
        fetch(`{{ route('opd.usulan.cari-item') }}?jenis=${currentJenis()}&q=${encodeURIComponent(q)}`)
            .then((r) => r.json())
            .then((data) => {
                (data.hasil || []).forEach((item) => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = 'list-group-item list-group-item-action';
                    a.textContent = item.label;
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        existingItemId.value = item.id;
                        cariTerpilih.textContent = `Dipilih: ${item.label}`;
                        cariHasil.innerHTML = '';
                        cariInput.value = item.label;
                        if (tipeSelect.value === 'perubahan') {
                            prefillFromItem(item);
                        }
                    });
                    cariHasil.appendChild(a);
                });
            });
    }

    function prefillFromItem(item) {
        const form = jenisSelect.closest('form');
        Object.entries(item).forEach(([key, value]) => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field && value !== null && value !== undefined) field.value = value;
        });
    }

    function resetPencarian() {
        existingItemId.value = '';
        cariTerpilih.textContent = '';
        cariInput.value = '';
        cariHasil.innerHTML = '';
    }

    jenisSelect?.addEventListener('change', () => { toggleFields(); resetPencarian(); });
    tipeSelect?.addEventListener('change', () => { toggleFields(); toggleExistingPicker(); resetPencarian(); });
    cariInput?.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(cariItem, 400); });

    toggleFields();
    toggleExistingPicker();

    // Anti-duplikasi (Bab 14 kajian) — hanya relevan untuk SSH.
    const uraian = document.getElementById('uraian');
    const merek = document.getElementById('merek');
    const hasil = document.getElementById('hasilSerupa');
    let dupTimer;

    function cekSerupa() {
        if (currentJenis() !== 'ssh') { hasil.innerHTML = ''; return; }
        const q = uraian.value.trim();
        if (q.length < 3) { hasil.innerHTML = ''; return; }
        fetch(`{{ route('opd.usulan.cek-serupa') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ uraian: q, merek: merek.value }),
        }).then((r) => r.json()).then((data) => {
            if (!data.hasil || data.hasil.length === 0) { hasil.innerHTML = ''; return; }
            let html = '<div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle"></i> <strong>Data serupa ditemukan — periksa dulu sebelum mengajukan:</strong><ul class="mb-0 mt-1">';
            data.hasil.forEach((r) => { html += `<li>${r.uraian} ${r.merek ? '(' + r.merek + ')' : ''} — Rp ${Number(r.harga).toLocaleString('id-ID')}</li>`; });
            html += '</ul></div>';
            hasil.innerHTML = html;
        });
    }
    uraian?.addEventListener('input', () => { clearTimeout(dupTimer); dupTimer = setTimeout(cekSerupa, 500); });
})();
</script>
@endpush
