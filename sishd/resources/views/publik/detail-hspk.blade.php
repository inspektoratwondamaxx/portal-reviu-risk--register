@extends('layouts.public')

@section('title', $item->uraian)

@section('content')
<div class="container py-4">
    <a href="{{ url()->previous() }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali</a>

    <div class="card mt-3">
        <div class="card-body">
            <span class="badge text-bg-warning text-dark mb-2">HSPK · {{ $item->kode }}</span>
            <h3>{{ $item->uraian }}</h3>
            <p class="text-muted mb-3">{{ $item->jenis_pekerjaan }} — Satuan {{ $item->satuan }}</p>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light"><tr><th>Komponen</th><th>Jenis</th><th class="text-end">Koefisien</th><th>Satuan</th><th class="text-end">Harga Satuan</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    @foreach ($item->components as $c)
                        <tr>
                            <td>{{ $c->label() }}</td>
                            <td><span class="badge text-bg-light border">{{ $c->komponen_type->label() }}</span></td>
                            <td class="text-end">{{ number_format($c->koefisien, 4, ',', '.') }}</td>
                            <td>{{ $c->satuan }}</td>
                            <td class="text-end">Rp {{ number_format($c->harga_satuan, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($c->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">TOTAL HSPK / {{ $item->satuan }}</td>
                            <td class="text-end">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
