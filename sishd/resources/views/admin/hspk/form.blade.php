@extends('layouts.app')

@section('title', $item->exists ? 'Ubah HSPK' : 'Tambah HSPK')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">{{ $item->exists ? 'Ubah HSPK' : 'Tambah HSPK' }}</h4>
    <small class="text-muted">Dashboard / Analisis / HSPK / {{ $item->exists ? 'Ubah' : 'Tambah' }}</small>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $item->exists ? route('admin.hspk.update', $item) : route('admin.hspk.store') }}">
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
                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                    <input type="text" name="satuan" value="{{ old('satuan', $item->satuan) }}" class="form-control" required placeholder="mis. M3, M2, Titik">
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
                    <label class="form-label">Uraian Pekerjaan <span class="text-danger">*</span></label>
                    <input type="text" name="uraian" value="{{ old('uraian', $item->uraian) }}" class="form-control" required placeholder="mis. Pekerjaan Beton K-250">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jenis Pekerjaan</label>
                    <input type="text" name="jenis_pekerjaan" value="{{ old('jenis_pekerjaan', $item->jenis_pekerjaan) }}" class="form-control" placeholder="mis. Struktur Beton">
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2">{{ old('catatan', $item->catatan) }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="{{ route('admin.hspk.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
