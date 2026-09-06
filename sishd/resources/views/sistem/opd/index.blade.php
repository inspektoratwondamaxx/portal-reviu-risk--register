@extends('layouts.app')

@section('title', 'Manajemen OPD')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Manajemen OPD</h4>
        <small class="text-muted">Dashboard / Sistem / OPD</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg"></i> Tambah OPD</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Kode</th><th>Nama</th><th>Singkatan</th><th class="text-end">Jml User</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $o)
                    <tr>
                        <td>{{ $o->kode }}</td>
                        <td>{{ $o->nama }}</td>
                        <td>{{ $o->singkatan }}</td>
                        <td class="text-end">{{ $o->users_count }}</td>
                        <td>{!! $o->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $o->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('sistem.opd.destroy', $o) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus OPD ini?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEdit{{ $o->id }}">
                        <div class="modal-dialog">
                            <form action="{{ route('sistem.opd.update', $o) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header"><h6 class="modal-title">Ubah OPD</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2"><label class="form-label">Kode</label><input type="text" name="kode" value="{{ $o->kode }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="nama" value="{{ $o->nama }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label">Singkatan</label><input type="text" name="singkatan" value="{{ $o->singkatan }}" class="form-control"></div>
                                    <div class="mb-2"><label class="form-label">Kepala OPD</label><input type="text" name="kepala_opd" value="{{ $o->kepala_opd }}" class="form-control"></div>
                                    <div class="mb-2"><label class="form-label">Telepon</label><input type="text" name="telepon" value="{{ $o->telepon }}" class="form-control"></div>
                                    <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active{{ $o->id }}" {{ $o->is_active ? 'checked' : '' }}><label class="form-check-label" for="active{{ $o->id }}">Aktif</label></div>
                                </div>
                                <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada OPD.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>

<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <form action="{{ route('sistem.opd.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h6 class="modal-title">Tambah OPD</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Kode</label><input type="text" name="kode" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Singkatan</label><input type="text" name="singkatan" class="form-control"></div>
                <div class="mb-2"><label class="form-label">Kepala OPD</label><input type="text" name="kepala_opd" class="form-control"></div>
                <div class="mb-2"><label class="form-label">Telepon</label><input type="text" name="telepon" class="form-control"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
@endsection
