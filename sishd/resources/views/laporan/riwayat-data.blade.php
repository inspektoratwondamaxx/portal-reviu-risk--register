@extends('layouts.app')

@section('title', 'Riwayat Data')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">Riwayat Data (Audit Trail)</h4>
    <small class="text-muted">Dashboard / Laporan / Riwayat Data</small>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Model</label>
                <input type="text" name="model" value="{{ request('model') }}" class="form-control" placeholder="mis. SshItem">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Aksi</label>
                <select name="aksi" class="form-select">
                    <option value="">Semua</option>
                    <option value="created" {{ request('aksi') === 'created' ? 'selected' : '' }}>Dibuat</option>
                    <option value="updated" {{ request('aksi') === 'updated' ? 'selected' : '' }}>Diubah</option>
                    <option value="deleted" {{ request('aksi') === 'deleted' ? 'selected' : '' }}>Dihapus</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sishd table-sm align-middle">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Model</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="small">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->user?->name ?: 'Sistem' }}</td>
                        <td class="small">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</td>
                        <td><span class="badge text-bg-light border">{{ $log->action }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </div>
</div>
@endsection
