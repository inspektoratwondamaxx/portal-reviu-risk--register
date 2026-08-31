@extends('layouts.app')

@section('title', $item->exists ? 'Ubah ASB' : 'Tambah ASB')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">{{ $item->exists ? 'Ubah ASB' : 'Tambah ASB' }}</h4>
    <small class="text-muted">Dashboard / Analisis / ASB / {{ $item->exists ? 'Ubah' : 'Tambah' }}</small>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $item->exists ? route('admin.asb.update', $item) : route('admin.asb.store') }}">
            @csrf
            @if ($item->exists) @method('PUT') @endif

            <div class="row g-3">
                @if (! $item->exists)
                    <div class="col-md-4">
                        <label class="form-label">Kode</label>
                        <input type="text" name="kode" class="form-control" placeholder="Otomatis jika dikosongkan">
                    </div>
                @endif
                <div class="col-md-{{ $item->exists ? 6 : 4 }}">
                    <label class="form-label">Kelompok Kegiatan</label>
                    <input type="text" name="kelompok_kegiatan" value="{{ old('kelompok_kegiatan', $item->kelompok_kegiatan) }}" class="form-control" placeholder="mis. Belanja Modal Gedung">
                </div>
                <div class="col-md-{{ $item->exists ? 6 : 4 }}">
                    <label class="form-label">Tahun Anggaran <span class="text-danger">*</span></label>
                    <select name="tahun_anggaran_id" class="form-select" required>
                        @foreach ($tahunList as $t)
                            <option value="{{ $t->id }}" {{ (int) old('tahun_anggaran_id', $item->tahun_anggaran_id ?? optional($tahunList->firstWhere('is_active', true))->id) === $t->id ? 'selected' : '' }}>{{ $t->tahun }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kegiatan" value="{{ old('nama_kegiatan', $item->nama_kegiatan) }}" class="form-control" required placeholder="mis. Pembangunan Gedung Pemerintah">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Satuan Variabel Utama</label>
                    <input type="text" name="satuan_variabel" value="{{ old('satuan_variabel', $item->satuan_variabel) }}" class="form-control" placeholder="mis. M2">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Batas Minimal (Rp)</label>
                    <input type="number" step="0.01" name="batas_minimal" value="{{ old('batas_minimal', $item->batas_minimal) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Batas Maksimal (Rp)</label>
                    <input type="number" step="0.01" name="batas_maksimal" value="{{ old('batas_maksimal', $item->batas_maksimal) }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2">{{ old('catatan', $item->catatan) }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="{{ route('admin.asb.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
