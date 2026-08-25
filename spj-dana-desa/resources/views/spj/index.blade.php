@extends('layouts.app')

@section('title', 'SPJ & Persetujuan')

@section('content')
<div class="bg-white rounded-lg border border-slate-200 p-4">
    <form method="GET" class="flex items-center gap-2 mb-4">
        <select name="status" class="rounded border border-slate-300 text-sm px-2 py-1.5" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach (['proses', 'diajukan', 'disetujui_pendamping', 'disetujui_inspektorat', 'revisi', 'final'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
            @endforeach
        </select>
    </form>

    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-slate-100">
                <th class="py-1.5">Kampung</th>
                <th class="py-1.5">Periode</th>
                <th class="py-1.5">Status</th>
                <th class="py-1.5 text-right">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($periodeSpj as $periode)
            <tr class="border-b border-slate-50 hover:bg-slate-50">
                <td class="py-2"><a class="text-sky-700 hover:underline" href="{{ route('dashboard.kampung', $periode->kampung) }}">{{ $periode->kampung->nama_kampung }}</a></td>
                <td class="py-2"><a class="text-sky-700 hover:underline" href="{{ route('spj.show', $periode) }}">{{ $periode->bulan }}/{{ $periode->tahun_anggaran }}</a></td>
                <td class="py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $periode->status) }}</span></td>
                <td class="py-2 text-right">Rp {{ number_format($periode->saldo_akhir, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-3 text-slate-400">Belum ada periode SPJ.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $periodeSpj->links() }}</div>
</div>
@endsection
