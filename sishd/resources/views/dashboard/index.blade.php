@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card stat-card bg-ssh">
                <div class="card-body">
                    <div class="stat-value">{{ number_format($counts['ssh']) }}</div>
                    <div>SSH · Data Aktif</div>
                    <a href="{{ route('admin.ssh.index') }}" class="text-white small">Lihat Detail <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card bg-sbu">
                <div class="card-body">
                    <div class="stat-value">{{ number_format($counts['sbu']) }}</div>
                    <div>SBU · Data Aktif</div>
                    <a href="{{ route('admin.sbu.index') }}" class="text-white small">Lihat Detail <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card bg-hspk">
                <div class="card-body">
                    <div class="stat-value">{{ number_format($counts['hspk']) }}</div>
                    <div>HSPK · Data Aktif</div>
                    <a href="{{ route('admin.hspk.index') }}" class="text-white small">Lihat Detail <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card bg-asb">
                <div class="card-body">
                    <div class="stat-value">{{ number_format($counts['asb']) }}</div>
                    <div>ASB · Data Aktif</div>
                    <a href="{{ route('admin.asb.index') }}" class="text-white small">Lihat Detail <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Usulan Baru (30 hari)</div>
                <div class="fs-4 fw-bold">{{ $proposalStats['baru'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Menunggu Verifikasi</div>
                <div class="fs-4 fw-bold text-warning">{{ $proposalStats['menunggu'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Disetujui (30 hari)</div>
                <div class="fs-4 fw-bold text-success">{{ $proposalStats['disetujui'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Ditolak (30 hari)</div>
                <div class="fs-4 fw-bold text-danger">{{ $proposalStats['ditolak'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title">Perkembangan Jumlah Data per Tahun</h6>
                <canvas id="chartTahun" height="110"></canvas>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title">Persentase Data per Jenis</h6>
                <canvas id="chartJenis" height="180"></canvas>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="card-title">Perubahan Harga Terbaru</h6>
            <div class="table-responsive">
                <table class="table table-sishd table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Uraian</th>
                            <th>Jenis</th>
                            <th class="text-end">Harga Lama</th>
                            <th class="text-end">Harga Baru</th>
                            <th class="text-end">Persentase</th>
                            <th>Tanggal</th>
                            <th>Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($perubahanTerbaru as $h)
                            @php $item = $h->item(); @endphp
                            <tr>
                                <td>{{ $item?->uraian ?? $item?->nama_kegiatan ?? '—' }}</td>
                                <td><span class="badge text-bg-secondary">{{ $h->itemLabel() }}</span></td>
                                <td class="text-end">Rp {{ number_format($h->harga_lama, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($h->harga_baru, 0, ',', '.') }}</td>
                                <td class="text-end {{ $h->harga_baru >= $h->harga_lama ? 'text-danger' : 'text-success' }}">
                                    <i class="bi bi-arrow-{{ $h->harga_baru >= $h->harga_lama ? 'up' : 'down' }}"></i>
                                    {{ number_format(abs($h->persentase), 2, ',', '.') }}%
                                </td>
                                <td>{{ $h->tanggal->format('d/m/Y') }}</td>
                                <td>{{ $h->user?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Belum ada perubahan harga tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const perTahun = @json($perTahun);
    new Chart(document.getElementById('chartTahun'), {
        type: 'line',
        data: {
            labels: perTahun.map(r => r.tahun),
            datasets: [
                { label: 'SSH', data: perTahun.map(r => r.ssh), borderColor: '#2563eb', tension: .3 },
                { label: 'SBU', data: perTahun.map(r => r.sbu), borderColor: '#16a34a', tension: .3 },
                { label: 'HSPK', data: perTahun.map(r => r.hspk), borderColor: '#ea580c', tension: .3 },
                { label: 'ASB', data: perTahun.map(r => r.asb), borderColor: '#7c3aed', tension: .3 },
            ],
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });

    const jenis = @json($persentaseJenis);
    new Chart(document.getElementById('chartJenis'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(jenis),
            datasets: [{ data: Object.values(jenis), backgroundColor: ['#2563eb', '#16a34a', '#ea580c', '#7c3aed'] }],
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });
});
</script>
@endpush
