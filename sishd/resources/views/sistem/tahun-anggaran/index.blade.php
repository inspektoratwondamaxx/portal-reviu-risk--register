@extends('layouts.app')

@section('title', 'Tahun Anggaran')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Tahun Anggaran</h4>
        <small class="text-muted">Dashboard / Sistem / Tahun Anggaran</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg"></i> Tambah Tahun</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Tahun</th><th>Status</th><th>Periode</th><th>Aktif</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $t)
                    <tr>
                        <td class="fw-bold">{{ $t->tahun }}</td>
                        <td><span class="badge text-bg-light border text-capitalize">{{ $t->status }}</span></td>
                        <td>{{ $t->tanggal_mulai?->format('d/m/Y') }} — {{ $t->tanggal_selesai?->format('d/m/Y') }}</td>
                        <td>{!! $t->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '' !!}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $t->id }}"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEdit{{ $t->id }}">
                        <div class="modal-dialog">
                            <form action="{{ route('sistem.tahun-anggaran.update', $t) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header"><h6 class="modal-title">Ubah Tahun {{ $t->tahun }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2"><label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            @foreach (['draft' => 'Draft', 'aktif' => 'Aktif', 'tutup' => 'Tutup'] as $val => $label)
                                                <option value="{{ $val }}" {{ $t->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Mengaktifkan tahun ini otomatis menonaktifkan tahun lain.</div>
                                    </div>
                                    <div class="mb-2"><label class="form-label">Tanggal Mulai</label><input type="date" name="tanggal_mulai" value="{{ $t->tanggal_mulai?->format('Y-m-d') }}" class="form-control"></div>
                                    <div class="mb-2"><label class="form-label">Tanggal Selesai</label><input type="date" name="tanggal_selesai" value="{{ $t->tanggal_selesai?->format('Y-m-d') }}" class="form-control"></div>
                                    <div class="mb-2"><label class="form-label">Keterangan</label><input type="text" name="keterangan" value="{{ $t->keterangan }}" class="form-control"></div>
                                </div>
                                <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada tahun anggaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <form action="{{ route('sistem.tahun-anggaran.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h6 class="modal-title">Tambah Tahun Anggaran</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Tahun</label><input type="number" name="tahun" class="form-control" required min="2000" max="2100"></div>
                <div class="mb-2"><label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft">Draft</option>
                        <option value="aktif">Aktif</option>
                        <option value="tutup">Tutup</option>
                    </select>
                </div>
                <div class="mb-2"><label class="form-label">Tanggal Mulai</label><input type="date" name="tanggal_mulai" class="form-control"></div>
                <div class="mb-2"><label class="form-label">Tanggal Selesai</label><input type="date" name="tanggal_selesai" class="form-control"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
@endsection
