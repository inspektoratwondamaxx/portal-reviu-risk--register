@extends('layouts.app')

@section('title', 'Kode Rekening')

@section('content')
<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Tambah Kode Rekening</h2>
    <form method="POST" action="{{ route('admin.kode-rekening.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
        @csrf
        <input name="kode" placeholder="Kode (mis. 5.1.3.01)" required class="rounded border border-slate-300 text-sm px-3 py-2">
        <input name="uraian" placeholder="Uraian" required class="rounded border border-slate-300 text-sm px-3 py-2 sm:col-span-2">
        <select name="jenis_belanja" required class="rounded border border-slate-300 text-sm px-3 py-2">
            <option value="pegawai">Pegawai</option>
            <option value="barang_jasa">Barang &amp; Jasa</option>
            <option value="modal">Modal</option>
            <option value="tak_terduga">Tak Terduga</option>
        </select>
        <input name="tahun_anggaran" type="number" placeholder="Tahun" required value="{{ date('Y') }}" class="rounded border border-slate-300 text-sm px-3 py-2">
        <button class="sm:col-span-5 justify-self-start rounded bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-800">Simpan</button>
    </form>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Daftar Kode Rekening ({{ $kodeRekeningList->total() }})</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-slate-100">
                <th class="py-1.5">Kode</th>
                <th class="py-1.5">Uraian</th>
                <th class="py-1.5">Jenis Belanja</th>
                <th class="py-1.5">Tahun</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kodeRekeningList as $kode)
            <tr class="border-b border-slate-50">
                <td class="py-2">{{ $kode->kode }}</td>
                <td class="py-2">{{ $kode->uraian }}</td>
                <td class="py-2">{{ str_replace('_', ' ', $kode->jenis_belanja) }}</td>
                <td class="py-2">{{ $kode->tahun_anggaran }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">{{ $kodeRekeningList->links() }}</div>
</div>
@endsection
