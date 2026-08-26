@extends('layouts.app')

@section('title', 'Kegiatan & Pagu')

@section('content')
<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Tambah Kegiatan</h2>
    <form method="POST" action="{{ route('admin.kegiatan.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @csrf
        <select name="kampung_id" required class="rounded border border-slate-300 text-sm px-3 py-2">
            <option value="">Pilih kampung</option>
            @foreach ($kampungList as $kampung)
            <option value="{{ $kampung->id }}">{{ $kampung->nama_kampung }}</option>
            @endforeach
        </select>
        <select name="bidang_kegiatan_id" required class="rounded border border-slate-300 text-sm px-3 py-2">
            <option value="">Pilih bidang</option>
            @foreach ($bidangList as $bidang)
            <option value="{{ $bidang->id }}">{{ $bidang->kode }} — {{ $bidang->nama_bidang }}</option>
            @endforeach
        </select>
        <input name="tahun_anggaran" type="number" placeholder="Tahun anggaran" required value="{{ date('Y') }}" class="rounded border border-slate-300 text-sm px-3 py-2">
        <input name="nama_kegiatan" placeholder="Nama kegiatan" required class="rounded border border-slate-300 text-sm px-3 py-2 sm:col-span-2">
        <input name="pagu_total" type="number" step="0.01" placeholder="Pagu total (Rp)" required class="rounded border border-slate-300 text-sm px-3 py-2">
        <button class="sm:col-span-3 justify-self-start rounded bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-800">Simpan</button>
    </form>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <form method="GET" class="mb-4">
        <select name="kampung_id" class="rounded border border-slate-300 text-sm px-2 py-1.5" onchange="this.form.submit()">
            <option value="">Semua kampung</option>
            @foreach ($kampungList as $kampung)
            <option value="{{ $kampung->id }}" @selected(request('kampung_id') == $kampung->id)>{{ $kampung->nama_kampung }}</option>
            @endforeach
        </select>
    </form>

    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-slate-100">
                <th class="py-1.5">Kampung</th>
                <th class="py-1.5">Kegiatan</th>
                <th class="py-1.5">Bidang</th>
                <th class="py-1.5 text-right">Pagu Total</th>
                <th class="py-1.5"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kegiatanList as $kegiatan)
            <tr class="border-b border-slate-50">
                <td class="py-2">{{ $kegiatan->kampung->nama_kampung }}</td>
                <td class="py-2">{{ $kegiatan->nama_kegiatan }}</td>
                <td class="py-2">{{ $kegiatan->bidangKegiatan->nama_bidang }}</td>
                <td class="py-2 text-right">Rp {{ number_format($kegiatan->pagu_total, 0, ',', '.') }}</td>
                <td class="py-2"><a class="text-sky-700 hover:underline text-xs" href="{{ route('admin.kegiatan.show', $kegiatan) }}">Atur Pagu Rekening</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">{{ $kegiatanList->links() }}</div>
</div>
@endsection
