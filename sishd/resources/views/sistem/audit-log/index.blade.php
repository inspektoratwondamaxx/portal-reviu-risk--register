@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')
<div class="mb-3">
    <h4 class="mb-0">Audit Log</h4>
    <small class="text-muted">Dashboard / Sistem / Audit Log</small>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
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
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Filter</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="accordion" id="auditAccordion">
        @forelse ($logs as $log)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed small" type="button" data-bs-toggle="collapse" data-bs-target="#log{{ $log->id }}">
                        <span class="badge text-bg-light border me-2">{{ $log->action }}</span>
                        {{ class_basename($log->model_type) }} #{{ $log->model_id }} — {{ $log->user?->name ?: 'Sistem' }} — {{ $log->created_at->format('d/m/Y H:i:s') }}
                    </button>
                </h2>
                <div id="log{{ $log->id }}" class="accordion-collapse collapse" data-bs-parent="#auditAccordion">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Data Sebelum</div>
                                <pre class="bg-light p-2 rounded small">{{ json_encode($log->data_sebelum, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Data Sesudah</div>
                                <pre class="bg-light p-2 rounded small">{{ json_encode($log->data_sesudah, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                        <div class="small text-muted mt-2">IP: {{ $log->ip_address ?: '-' }}</div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-muted py-4 mb-0">Belum ada aktivitas tercatat.</p>
        @endforelse
        </div>
        {{ $logs->links() }}
    </div>
</div>
@endsection
