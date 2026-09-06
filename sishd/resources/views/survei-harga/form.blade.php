@extends('layouts.app')

@section('title', 'Input Survei Harga')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">Input Survei Harga</h4>
    <small class="text-muted">Dashboard / Survei Harga / Input Survei</small>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('survei-harga.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Kaitkan dengan SSH (opsional)</label>
                    <select name="ssh_item_id" class="form-select">
                        <option value="">-- Barang belum/tidak ada di master --</option>
                        @foreach ($sshItems as $ssh)
                            <option value="{{ $ssh->id }}">{{ $ssh->uraian }} ({{ $ssh->merek }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Penyedia / Toko</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Uraian Barang <span class="text-danger">*</span></label>
                    <input type="text" name="uraian_barang" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Merek</label>
                    <input type="text" name="merek" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Spesifikasi</label>
                    <input type="text" name="spesifikasi" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-control" placeholder="mis. Kota Gresik">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Survei <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_survei" value="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="harga" class="form-control" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2"></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Jenis Bukti</label>
                    <select name="jenis_bukti" class="form-select">
                        @foreach (\App\Models\PriceEvidence::JENIS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Foto Bukti (boleh lebih dari satu)</label>
                    <input type="file" name="bukti[]" class="form-control" accept="image/*" multiple>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Survei</button>
                <a href="{{ route('survei-harga.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
