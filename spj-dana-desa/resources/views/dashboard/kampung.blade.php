@extends('layouts.app')

@section('title', $kampung->nama_kampung)

@section('content')
<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-1">{{ $kampung->nama_kampung }} ({{ $kampung->kode_kampung }})</h2>
    <p class="text-sm text-slate-500">Kecamatan {{ $kampung->kecamatan }}</p>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Realisasi per Kegiatan (Tahun Berjalan)</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-slate-100">
                <th class="py-1.5">Kegiatan</th>
                <th class="py-1.5 text-right">Pagu</th>
                <th class="py-1.5 text-right">Realisasi</th>
                <th class="py-1.5 text-right">% Realisasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($realisasiPerKegiatan as $kegiatan)
            @php $realisasi = $kegiatan->total_realisasi ?? 0; @endphp
            <tr class="border-b border-slate-50">
                <td class="py-2">{{ $kegiatan->nama_kegiatan }}</td>
                <td class="py-2 text-right">Rp {{ number_format($kegiatan->pagu_total, 0, ',', '.') }}</td>
                <td class="py-2 text-right">Rp {{ number_format($realisasi, 0, ',', '.') }}</td>
                <td class="py-2 text-right">{{ $kegiatan->pagu_total > 0 ? number_format($realisasi / $kegiatan->pagu_total * 100, 1) : 0 }}%</td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-3 text-slate-400">Belum ada kegiatan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Riwayat Periode SPJ</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-slate-100">
                <th class="py-1.5">Periode</th>
                <th class="py-1.5">Status</th>
                <th class="py-1.5 text-right">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($periodeSpj as $periode)
            <tr class="border-b border-slate-50 hover:bg-slate-50">
                <td class="py-2"><a class="text-sky-700 hover:underline" href="{{ route('spj.show', $periode) }}">{{ $periode->bulan }}/{{ $periode->tahun_anggaran }}</a></td>
                <td class="py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $periode->status) }}</span></td>
                <td class="py-2 text-right">Rp {{ number_format($periode->saldo_akhir, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="py-3 text-slate-400">Belum ada periode SPJ.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
