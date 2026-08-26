@extends('layouts.app')

@section('title', 'Ringkasan')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Total Kampung Terjangkau</p>
        <p class="text-2xl font-semibold mt-1">{{ $totalKampung }}</p>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Total Realisasi Belanja</p>
        <p class="text-2xl font-semibold mt-1">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Menunggu Persetujuan</p>
        <p class="text-2xl font-semibold mt-1">{{ $periodeMenungguPersetujuan->count() }}</p>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Transaksi Ter-flag Anomali</p>
        <p class="text-2xl font-semibold mt-1 text-rose-600">{{ $transaksiFlagged->count() }}</p>
    </div>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Sebaran Status SPJ</h2>
    <div class="flex flex-wrap gap-2">
        @forelse ($statusSpj as $status => $jumlah)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs">
            <span class="font-medium">{{ $jumlah }}</span> {{ str_replace('_', ' ', $status) }}
        </span>
        @empty
        <p class="text-sm text-slate-400">Belum ada data periode SPJ.</p>
        @endforelse
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <h2 class="text-sm font-semibold mb-3">SPJ Menunggu Persetujuan</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b border-slate-100">
                    <th class="py-1.5">Kampung</th>
                    <th class="py-1.5">Periode</th>
                    <th class="py-1.5">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($periodeMenungguPersetujuan as $periode)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-2"><a class="text-sky-700 hover:underline" href="{{ route('spj.show', $periode) }}">{{ $periode->kampung->nama_kampung }}</a></td>
                    <td class="py-2">{{ $periode->bulan }}/{{ $periode->tahun_anggaran }}</td>
                    <td class="py-2"><span class="rounded-full bg-amber-100 text-amber-800 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $periode->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-3 text-slate-400">Tidak ada SPJ menunggu persetujuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <h2 class="text-sm font-semibold mb-3">Transaksi Ter-flag Anomali Terbaru</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b border-slate-100">
                    <th class="py-1.5">Kampung</th>
                    <th class="py-1.5">Uraian</th>
                    <th class="py-1.5 text-right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaksiFlagged as $transaksi)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="py-2"><a class="text-sky-700 hover:underline" href="{{ route('transaksi.show', $transaksi) }}">{{ $transaksi->kampung->nama_kampung }}</a></td>
                    <td class="py-2">{{ \Illuminate\Support\Str::limit($transaksi->uraian, 40) }}</td>
                    <td class="py-2 text-right">Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-3 text-slate-400">Tidak ada transaksi ter-flag.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
