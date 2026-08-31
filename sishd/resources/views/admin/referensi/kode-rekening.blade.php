@extends('layouts.app')

@section('title', 'Kode Rekening')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Kode Rekening Belanja (BAS)</h4>
        <small class="text-muted">Dashboard / Master Data / Kode Rekening</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg"></i> Tambah</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Kode</th><th>Uraian</th><th>Induk</th><th>Jenis Belanja</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $row)
                    <tr>
                        <td>{{ $row->kode }}</td>
                        <td>{{ $row->uraian }}</td>
                        <td>{{ $row->parent?->kode ?: '-' }}</td>
                        <td>{{ $row->jenis_belanja }}</td>
                        <td>{!! $row->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $row->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.kode-rekening.destroy', $row) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kode rekening ini?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEdit{{ $row->id }}">
                        <div class="modal-dialog">
                            <form action="{{ route('admin.kode-rekening.update', $row) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header"><h6 class="modal-title">Ubah Kode Rekening</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2"><label class="form-label">Kode</label><input type="text" name="kode" value="{{ $row->kode }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label">Uraian</label><input type="text" name="uraian" value="{{ $row->uraian }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label">Induk</label>
                                        <select name="parent_id" class="form-select">
                                            <option value="">-- Tidak ada --</option>
                                            @foreach ($parents as $p)
                                                @if ($p->id !== $row->id)
                                                    <option value="{{ $p->id }}" {{ $row->parent_id === $p->id ? 'selected' : '' }}>{{ $p->kode }} — {{ $p->uraian }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2"><label class="form-label">Jenis Belanja</label><input type="text" name="jenis_belanja" value="{{ $row->jenis_belanja }}" class="form-control"></div>
                                    <div class="mb-2"><label class="form-label">Level</label><input type="number" name="level" min="1" max="5" value="{{ $row->level }}" class="form-control" required></div>
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
        <form action="{{ route('admin.kode-rekening.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h6 class="modal-title">Tambah Kode Rekening</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Kode</label><input type="text" name="kode" class="form-control" required placeholder="mis. 5.1.02.01.02"></div>
                <div class="mb-2"><label class="form-label">Uraian</label><input type="text" name="uraian" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Induk</label>
                    <select name="parent_id" class="form-select">
                        <option value="">-- Tidak ada --</option>
                        @foreach ($parents as $p)
                            <option value="{{ $p->id }}">{{ $p->kode }} — {{ $p->uraian }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2"><label class="form-label">Jenis Belanja</label><input type="text" name="jenis_belanja" class="form-control"></div>
                <div class="mb-2"><label class="form-label">Level</label><input type="number" name="level" min="1" max="5" value="1" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
@endsection
