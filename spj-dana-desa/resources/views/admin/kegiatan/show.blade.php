@extends('layouts.app')

@section('title', $kegiatan->nama_kegiatan)

@section('content')
<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold">{{ $kegiatan->nama_kegiatan }}</h2>
    <p class="text-sm text-slate-500 mt-1">{{ $kegiatan->kampung->nama_kampung }} &middot; {{ $kegiatan->bidangKegiatan->nama_bidang }} &middot; TA {{ $kegiatan->tahun_anggaran }}</p>
    <p class="text-sm mt-2">Pagu total: <span class="font-semibold">Rp {{ number_format($kegiatan->pagu_total, 0, ',', '.') }}</span></p>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Tetapkan Pagu per Kode Rekening</h2>
    <form method="POST" action="{{ route('admin.kegiatan.pagu', $kegiatan) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @csrf @method('PUT')
        <select name="kode_rekening_id" required class="rounded border border-slate-300 text-sm px-3 py-2">
            <option value="">Pilih kode rekening</option>
            @foreach ($kodeRekeningList as $kode)
            <option value="{{ $kode->id }}">{{ $kode->kode }} — {{ $kode->uraian }}</option>
            @endforeach
        </select>
        <input name="pagu_anggaran" type="number" step="0.01" placeholder="Pagu (Rp)" required class="rounded border border-slate-300 text-sm px-3 py-2">
        <button class="rounded bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-800">Simpan</button>
    </form>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Pagu Rekening Tersimpan</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-slate-100">
                <th class="py-1.5">Kode</th>
                <th class="py-1.5">Uraian</th>
                <th class="py-1.5 text-right">Pagu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($kegiatan->paguRekening as $pagu)
            <tr class="border-b border-slate-50">
                <td class="py-2">{{ $pagu->kodeRekening->kode }}</td>
                <td class="py-2">{{ $pagu->kodeRekening->uraian }}</td>
                <td class="py-2 text-right">Rp {{ number_format($pagu->pagu_anggaran, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="py-3 text-slate-400">Belum ada pagu rekening ditetapkan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
