@extends('layouts.app')

@section('title', $item->exists ? 'Ubah SBU' : 'Tambah SBU')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">{{ $item->exists ? 'Ubah Data SBU' : 'Tambah SBU' }}</h4>
    <small class="text-muted">Dashboard / Master Data / SBU / {{ $item->exists ? 'Ubah' : 'Tambah' }}</small>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $item->exists ? route('admin.sbu.update', $item) : route('admin.sbu.store') }}">
            @csrf
            @if ($item->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Kode</label>
                    <input type="text" name="kode" value="{{ old('kode', $item->kode) }}" class="form-control" placeholder="Otomatis jika dikosongkan">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="kategori" class="form-select" required>
                        @foreach ($kategoriOptions as $val => $label)
                            <option value="{{ $val }}" {{ old('kategori', $item->kategori) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tahun Anggaran <span class="text-danger">*</span></label>
                    <select name="tahun_anggaran_id" class="form-select" required>
                        @foreach ($tahunList as $t)
                            <option value="{{ $t->id }}" {{ (int) old('tahun_anggaran_id', $item->tahun_anggaran_id ?? optional($tahunList->firstWhere('is_active', true))->id) === $t->id ? 'selected' : '' }}>{{ $t->tahun }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Uraian <span class="text-danger">*</span></label>
                    <input type="text" name="uraian" value="{{ old('uraian', $item->uraian) }}" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                    <input type="text" name="satuan" value="{{ old('satuan', $item->satuan) }}" class="form-control" required placeholder="mis. OH, OJ, Hari">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Wilayah</label>
                    <input type="text" name="wilayah" value="{{ old('wilayah', $item->wilayah) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Besaran (Rp) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="besaran" value="{{ old('besaran', $item->besaran) }}" class="form-control" required>
                </div>

                @if ($item->exists)
                    <div class="col-12">
                        <label class="form-label">Dasar Perubahan</label>
                        <input type="text" name="dasar_perubahan" class="form-control" placeholder="mis. SK Bupati Upah Minimum 2026">
                        <div class="form-text">Diisi hanya jika mengubah besaran — akan tercatat di riwayat &amp; memicu hitung ulang HSPK terkait.</div>
                    </div>
                @endif

                <div class="col-md-6">
                    <label class="form-label">Dasar Penetapan</label>
                    <input type="text" name="dasar_penetapan" value="{{ old('dasar_penetapan', $item->dasar_penetapan) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan', $item->keterangan) }}" class="form-control">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="{{ route('admin.sbu.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
