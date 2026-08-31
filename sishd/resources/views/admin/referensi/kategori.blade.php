@extends('layouts.app')

@section('title', 'Kategori')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Kategori Barang/Jasa</h4>
        <small class="text-muted">Dashboard / Master Data / Kategori</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg"></i> Tambah</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Kode</th><th>Nama</th><th>Induk</th><th>Jenis</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $row)
                    <tr>
                        <td>{{ $row->kode }}</td>
                        <td>{{ $row->nama }}</td>
                        <td>{{ $row->parent?->nama ?: '-' }}</td>
                        <td>{{ $row->jenis }}</td>
                        <td>{!! $row->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $row->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.kategori.destroy', $row) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEdit{{ $row->id }}">
                        <div class="modal-dialog">
                            <form action="{{ route('admin.kategori.update', $row) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header"><h6 class="modal-title">Ubah Kategori</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2"><label class="form-label">Kode</label><input type="text" name="kode" value="{{ $row->kode }}" class="form-control"></div>
                                    <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="nama" value="{{ $row->nama }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label">Induk</label>
                                        <select name="parent_id" class="form-select">
                                            <option value="">-- Tidak ada --</option>
                                            @foreach ($parents as $p)
                                                @if ($p->id !== $row->id)
                                                    <option value="{{ $p->id }}" {{ $row->parent_id === $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2"><label class="form-label">Jenis</label>
                                        <select name="jenis" class="form-select">
                                            @foreach (['material' => 'Material', 'upah' => 'Upah', 'peralatan' => 'Peralatan', 'jasa' => 'Jasa', 'lainnya' => 'Lainnya'] as $val => $label)
                                                <option value="{{ $val }}" {{ $row->jenis === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active{{ $row->id }}" {{ $row->is_active ? 'checked' : '' }}><label class="form-check-label" for="active{{ $row->id }}">Aktif</label></div>
                                </div>
                                <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada kategori.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>

<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <form action="{{ route('admin.kategori.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h6 class="modal-title">Tambah Kategori</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Kode</label><input type="text" name="kode" class="form-control"></div>
                <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Induk</label>
                    <select name="parent_id" class="form-select">
                        <option value="">-- Tidak ada --</option>
                        @foreach ($parents as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2"><label class="form-label">Jenis</label>
                    <select name="jenis" class="form-select">
                        @foreach (['material' => 'Material', 'upah' => 'Upah', 'peralatan' => 'Peralatan', 'jasa' => 'Jasa', 'lainnya' => 'Lainnya'] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
@endsection
