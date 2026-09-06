@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Manajemen User</h4>
        <small class="text-muted">Dashboard / Sistem / User</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg"></i> Tambah User</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-hover align-middle">
                <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>OPD</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td><span class="badge text-bg-light border">{{ $u->role?->label }}</span></td>
                        <td>{{ $u->opd?->singkatan ?: '-' }}</td>
                        <td>{!! $u->is_active ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $u->id }}"><i class="bi bi-pencil"></i></button>
                            @if ($u->id !== auth()->id())
                                <form action="{{ route('sistem.users.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEdit{{ $u->id }}">
                        <div class="modal-dialog">
                            <form action="{{ route('sistem.users.update', $u) }}" method="POST" class="modal-content">
                                @csrf @method('PUT')
                                <div class="modal-header"><h6 class="modal-title">Ubah User</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="name" value="{{ $u->name }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" value="{{ $u->email }}" class="form-control" required></div>
                                    <div class="mb-2"><label class="form-label">Role</label>
                                        <select name="role_id" class="form-select" required>
                                            @foreach ($roles as $r)
                                                <option value="{{ $r->id }}" {{ $u->role_id === $r->id ? 'selected' : '' }}>{{ $r->label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2"><label class="form-label">OPD</label>
                                        <select name="opd_id" class="form-select">
                                            <option value="">-- Tidak ada --</option>
                                            @foreach ($opds as $o)
                                                <option value="{{ $o->id }}" {{ $u->opd_id === $o->id ? 'selected' : '' }}>{{ $o->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2"><label class="form-label">Kata Sandi Baru (opsional)</label><input type="password" name="password" class="form-control"></div>
                                    <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active{{ $u->id }}" {{ $u->is_active ? 'checked' : '' }}><label class="form-check-label" for="active{{ $u->id }}">Aktif</label></div>
                                </div>
                                <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada user.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
</div>

<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <form action="{{ route('sistem.users.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h6 class="modal-title">Tambah User</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Role</label>
                    <select name="role_id" class="form-select" required>
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2"><label class="form-label">OPD</label>
                    <select name="opd_id" class="form-select">
                        <option value="">-- Tidak ada --</option>
                        @foreach ($opds as $o)
                            <option value="{{ $o->id }}">{{ $o->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2"><label class="form-label">Kata Sandi</label><input type="password" name="password" class="form-control" required minlength="8"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
@endsection
