@extends('layouts.app')

@section('title', 'Kelompok Barang')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Kelompok Barang</h4>
        <small class="text-muted">Dashboard / Master Data / Kelompok Barang</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg"></i> Tambah</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Kode</th><th>Nama</th><th>Keterangan</th><th class="text-end">Jml Kode Aset</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $row)
                    <tr>
                        <td>{{ $row->kode }}</td>
                        <td>{{ $row->nama }}</td>
                        <td class="text-muted small">{{ $row->keterangan }}</td>
                        <td class="text-end">{{ $row->asset_codes_count }}</td>
                        <td>{!! $row->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $row->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.kelompok-barang.destroy', $row) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kelompok barang ini?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEdit{{ $row->id }}">
                        <div class="modal-dialog">
                            <form action="{{ route('admin.kelompok-barang.update', $row) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header"><h6 class="modal-title">Ubah Kelompok Barang</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2"><label class="form-label">Kode</label><input type="text" name="kode" value="{{ $row->kode }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="nama" value="{{ $row->nama }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label">Keterangan</label><input type="text" name="keterangan" value="{{ $row->keterangan }}" class="form-control"></div>
                                    <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active{{ $row->id }}" {{ $row->is_active ? 'checked' : '' }}><label class="form-check-label" for="active{{ $row->id }}">Aktif</label></div>
                                </div>
                                <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>

<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <form action="{{ route('admin.kelompok-barang.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h6 class="modal-title">Tambah Kelompok Barang</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Kode</label><input type="text" name="kode" class="form-control" required placeholder="mis. 1.3.04"></div>
                <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Keterangan</label><input type="text" name="keterangan" class="form-control"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
@endsection
