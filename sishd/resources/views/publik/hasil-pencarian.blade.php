@extends('layouts.public')

@section('title', 'Hasil Pencarian')

@section('content')
<div class="container py-4">
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('publik.cari') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Kata Kunci</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Nama item...">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Jenis</label>
                    <select name="jenis" class="form-select">
                        @foreach (['ssh' => 'SSH', 'sbu' => 'SBU', 'hspk' => 'HSPK', 'asb' => 'ASB'] as $val => $label)
                            <option value="{{ $val }}" {{ $jenis === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Tahun</label>
                    <select name="tahun" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($tahunList as $t)
                            <option value="{{ $t->tahun }}" {{ (string) ($filters['tahun'] ?? '') === (string) $t->tahun ? 'selected' : '' }}>{{ $t->tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Kode</label>
                    <input type="text" name="kode" value="{{ $filters['kode'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
                </div>
            </form>
        </div>
    </div>

    <p class="text-muted">Menampilkan {{ $results->total() }} hasil untuk jenis <strong>{{ strtoupper($jenis) }}</strong>.</p>

    <div class="table-responsive">
        <table class="table table-hover bg-white align-middle">
            <thead class="table-light">
                @if ($jenis === 'ssh')
                    <tr><th>Kode</th><th>Uraian</th><th>Spesifikasi</th><th>Merek</th><th>Satuan</th><th class="text-end">Harga</th></tr>
                @elseif ($jenis === 'sbu')
                    <tr><th>Kode</th><th>Kategori</th><th>Uraian</th><th>Satuan</th><th class="text-end">Besaran</th></tr>
                @elseif ($jenis === 'hspk')
                    <tr><th>Kode</th><th>Uraian Pekerjaan</th><th>Satuan</th><th class="text-end">Harga Satuan</th></tr>
                @else
                    <tr><th>Kode</th><th>Nama Kegiatan</th><th>Kelompok</th><th class="text-end">Hasil Perhitungan</th></tr>
                @endif
            </thead>
            <tbody>
                @forelse ($results as $row)
                    @if ($jenis === 'ssh')
                        <tr>
                            <td><a href="{{ route('publik.ssh.show', $row) }}">{{ $row->kode_barang }}</a></td>
                            <td>{{ $row->uraian }}</td>
                            <td>{{ $row->spesifikasi }}</td>
                            <td>{{ $row->merek }}</td>
                            <td>{{ $row->satuan }}</td>
                            <td class="text-end">Rp {{ number_format($row->harga, 0, ',', '.') }}</td>
                        </tr>
                    @elseif ($jenis === 'sbu')
                        <tr>
                            <td>{{ $row->kode }}</td>
                            <td>{{ \App\Models\SbuItem::KATEGORI[$row->kategori] ?? $row->kategori }}</td>
                            <td>{{ $row->uraian }}</td>
                            <td>{{ $row->satuan }}</td>
                            <td class="text-end">Rp {{ number_format($row->besaran, 0, ',', '.') }}</td>
                        </tr>
                    @elseif ($jenis === 'hspk')
                        <tr>
                            <td><a href="{{ route('publik.hspk.show', $row) }}">{{ $row->kode }}</a></td>
                            <td>{{ $row->uraian }}</td>
                            <td>{{ $row->satuan }}</td>
                            <td class="text-end">Rp {{ number_format($row->harga_satuan, 0, ',', '.') }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $row->kode }}</td>
                            <td>{{ $row->nama_kegiatan }}</td>
                            <td>{{ $row->kelompok_kegiatan }}</td>
                            <td class="text-end">Rp {{ number_format($row->hasil_perhitungan, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $results->links() }}
</div>
@endsection
