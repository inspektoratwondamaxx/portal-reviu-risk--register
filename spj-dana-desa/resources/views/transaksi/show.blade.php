@extends('layouts.app')

@section('title', 'Detail Transaksi')

@section('content')
<div class="bg-white rounded-lg border border-slate-200 p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h2 class="text-sm font-semibold">{{ $transaksi->uraian }}</h2>
            <p class="text-xs text-slate-500 mt-1">{{ $transaksi->kampung->nama_kampung }} &middot; {{ $transaksi->kegiatan->nama_kegiatan }} &middot; {{ $transaksi->kodeRekening->kode }}</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs">{{ str_replace('_', ' ', $transaksi->status) }}</span>
    </div>

    @if ($transaksi->is_flagged)
    <div class="mt-3 rounded border border-amber-200 bg-amber-50 text-amber-900 px-3 py-2 text-sm">
        &#9888; Ditandai anomali oleh sistem: {{ $transaksi->catatan_flag }}
    </div>
    @endif

    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 text-sm">
        <div><dt class="text-slate-500 text-xs">Tanggal</dt><dd>{{ $transaksi->tanggal_transaksi->format('d-m-Y') }}</dd></div>
        <div><dt class="text-slate-500 text-xs">Nominal</dt><dd>Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}</dd></div>
        <div><dt class="text-slate-500 text-xs">Sumber Input</dt><dd>{{ $transaksi->sumber_input }}</dd></div>
        <div><dt class="text-slate-500 text-xs">Dibuat Offline</dt><dd>{{ $transaksi->dibuat_offline ? 'Ya' : 'Tidak' }}</dd></div>
    </dl>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Bukti Transaksi (Foto + GPS)</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @forelse ($transaksi->buktiTransaksi as $bukti)
        <div class="border border-slate-200 rounded-lg overflow-hidden">
            <div class="aspect-video bg-slate-100 flex items-center justify-center text-xs text-slate-400">
                Foto bukti tersimpan pada object storage
            </div>
            <div class="p-3 text-xs space-y-1">
                <p>Lat/Long: {{ $bukti->latitude }}, {{ $bukti->longitude }}</p>
                <p>Diambil: {{ $bukti->diambil_at->format('d-m-Y H:i') }}</p>
                <p>Status OCR: <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $bukti->status_ocr }}</span></p>
            </div>
        </div>
        @empty
        <p class="text-sm text-slate-400 col-span-full">Belum ada bukti transaksi.</p>
        @endforelse
    </div>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Riwayat Status</h2>
    <ol class="space-y-2 text-sm">
        @forelse ($transaksi->riwayatStatus as $riwayat)
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
