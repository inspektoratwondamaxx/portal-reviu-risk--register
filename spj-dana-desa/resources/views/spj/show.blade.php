@extends('layouts.app')

@section('title', 'SPJ ' . $periodeSpj->kampung->nama_kampung)

@section('content')
<div class="bg-white rounded-lg border border-slate-200 p-4 flex items-center justify-between flex-wrap gap-3">
    <div>
        <h2 class="text-sm font-semibold">{{ $periodeSpj->kampung->nama_kampung }} — Periode {{ $periodeSpj->bulan }}/{{ $periodeSpj->tahun_anggaran }}</h2>
        <p class="text-xs text-slate-500 mt-1">Status saat ini: <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ str_replace('_', ' ', $periodeSpj->status) }}</span></p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        @can('ajukan', $periodeSpj)
        <form method="POST" action="{{ route('spj.ajukan', $periodeSpj) }}">
            @csrf
            <button class="rounded bg-slate-900 text-white text-xs font-medium px-3 py-2 hover:bg-slate-800">Ajukan Periode</button>
        </form>
        @endcan

        @can('setujui', $periodeSpj)
        <details class="relative">
            <summary class="list-none cursor-pointer rounded bg-emerald-600 text-white text-xs font-medium px-3 py-2 hover:bg-emerald-700 inline-block">Setujui</summary>
            <div class="absolute right-0 mt-2 w-72 bg-white border border-slate-200 rounded-lg shadow-lg p-3 z-10">
                <form method="POST" action="{{ route('spj.setujui', $periodeSpj) }}" class="space-y-2">
                    @csrf
                    <textarea name="catatan" rows="2" placeholder="Catatan (opsional)" class="w-full rounded border border-slate-300 text-xs px-2 py-1.5"></textarea>
                    <button class="w-full rounded bg-emerald-600 text-white text-xs font-medium px-3 py-1.5 hover:bg-emerald-700">Konfirmasi Setujui</button>
                </form>
            </div>
        </details>

        <details class="relative">
            <summary class="list-none cursor-pointer rounded bg-rose-600 text-white text-xs font-medium px-3 py-2 hover:bg-rose-700 inline-block">Tolak / Revisi</summary>
            <div class="absolute right-0 mt-2 w-72 bg-white border border-slate-200 rounded-lg shadow-lg p-3 z-10">
                <form method="POST" action="{{ route('spj.tolak', $periodeSpj) }}" class="space-y-2">
                    @csrf
                    <textarea name="catatan" rows="2" required placeholder="Catatan penolakan (wajib)" class="w-full rounded border border-slate-300 text-xs px-2 py-1.5"></textarea>
                    <button class="w-full rounded bg-rose-600 text-white text-xs font-medium px-3 py-1.5 hover:bg-rose-700">Konfirmasi Tolak</button>
                </form>
            </div>
        </details>
        @endcan

        @can('generatePdf', $periodeSpj)
        @if ($periodeSpj->status === 'disetujui_inspektorat')
        <form method="POST" action="{{ route('spj.generate-pdf', $periodeSpj) }}">
            @csrf
            <button class="rounded bg-indigo-600 text-white text-xs font-medium px-3 py-2 hover:bg-indigo-700">Buat Dokumen SPJ</button>
        </form>
        @endif
        @if ($periodeSpj->dokumen->isNotEmpty())
        <a href="{{ route('spj.unduh', $periodeSpj) }}" class="rounded border border-slate-300 text-xs font-medium px-3 py-2 hover:bg-slate-50">Unduh PDF Terbaru</a>
        @endif
        @endcan

        @can('exportSiskeudes', $periodeSpj)
        <a href="{{ route('spj.export-siskeudes', $periodeSpj) }}" class="rounded border border-slate-300 text-xs font-medium px-3 py-2 hover:bg-slate-50">Ekspor Siskeudes (CSV)</a>
        @endcan
    </div>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Buku Kas Umum — Transaksi Periode Ini</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-slate-100">
                <th class="py-1.5">Tanggal</th>
                <th class="py-1.5">Kode Rek.</th>
                <th class="py-1.5">Uraian</th>
                <th class="py-1.5 text-right">Nominal</th>
                <th class="py-1.5">Status</th>
                <th class="py-1.5"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($periodeSpj->transaksis as $transaksi)
            <tr class="border-b border-slate-50 hover:bg-slate-50 {{ $transaksi->is_flagged ? 'bg-amber-50' : '' }}">
                <td class="py-2">{{ $transaksi->tanggal_transaksi->format('d-m-Y') }}</td>
                <td class="py-2">{{ $transaksi->kodeRekening->kode }}</td>
                <td class="py-2">{{ $transaksi->uraian }} @if($transaksi->is_flagged)<span class="ml-1 text-amber-700 text-xs">&#9888; anomali</span>@endif</td>
                <td class="py-2 text-right">Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}</td>
                <td class="py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $transaksi->status) }}</span></td>
                <td class="py-2"><a class="text-sky-700 hover:underline text-xs" href="{{ route('transaksi.show', $transaksi) }}">Detail</a></td>
            </tr>
            @empty
            <tr><td colspan="6" class="py-3 text-slate-400">Belum ada transaksi terlampir pada periode ini.</td></tr>
            @endforelse
        </tbody>
        @if ($periodeSpj->transaksis->isNotEmpty())
        <tfoot>
            <tr class="font-semibold border-t border-slate-200">
                <td class="py-2" colspan="3">Total</td>
                <td class="py-2 text-right">Rp {{ number_format($periodeSpj->transaksis->sum('nominal'), 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Riwayat Status</h2>
    <ol class="space-y-2 text-sm">
        @forelse ($periodeSpj->riwayatStatus as $riwayat)
        <li class="flex items-start gap-3">
            <span class="mt-1 h-2 w-2 rounded-full bg-slate-400 shrink-0"></span>
            <div>
                <p><span class="font-medium">{{ str_replace('_', ' ', $riwayat->status_baru) }}</span> oleh {{ $riwayat->pengubah?->name ?? '-' }} &middot; {{ $riwayat->changed_at->format('d-m-Y H:i') }}</p>
                @if ($riwayat->catatan)
                <p class="text-slate-500">{{ $riwayat->catatan }}</p>
                @endif
            </div>
        </li>
        @empty
        <li class="text-slate-400">Belum ada riwayat status.</li>
        @endforelse
    </ol>
</div>
@endsection
