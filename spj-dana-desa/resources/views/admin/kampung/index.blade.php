@extends('layouts.app')

@section('title', 'Master Data Kampung')

@section('content')
<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Tambah Kampung</h2>
    <form method="POST" action="{{ route('admin.kampung.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        @csrf
        <input name="kode_kampung" placeholder="Kode kampung" required class="rounded border border-slate-300 text-sm px-3 py-2">
        <input name="nama_kampung" placeholder="Nama kampung" required class="rounded border border-slate-300 text-sm px-3 py-2 sm:col-span-2">
        <input name="kecamatan" placeholder="Kecamatan" required class="rounded border border-slate-300 text-sm px-3 py-2">
        <button class="sm:col-span-4 justify-self-start rounded bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-800">Simpan</button>
    </form>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Daftar Kampung ({{ $kampungList->total() }})</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-slate-100">
                <th class="py-1.5">Kode</th>
                <th class="py-1.5">Nama</th>
                <th class="py-1.5">Kecamatan</th>
                <th class="py-1.5">Status</th>
                <th class="py-1.5"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kampungList as $kampung)
            <tr class="border-b border-slate-50">
                <td class="py-2">{{ $kampung->kode_kampung }}</td>
                <td class="py-2">{{ $kampung->nama_kampung }}</td>
                <td class="py-2">{{ $kampung->kecamatan }}</td>
                <td class="py-2">
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $kampung->status_aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ $kampung->status_aktif ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="py-2">
                    <details class="relative">
                        <summary class="list-none cursor-pointer text-sky-700 hover:underline text-xs">Ubah</summary>
                        <div class="absolute right-0 mt-2 w-72 bg-white border border-slate-200 rounded-lg shadow-lg p-3 z-10">
                            <form method="POST" action="{{ route('admin.kampung.update', $kampung) }}" class="space-y-2">
                                @csrf @method('PUT')
                                <input name="nama_kampung" value="{{ $kampung->nama_kampung }}" class="w-full rounded border border-slate-300 text-xs px-2 py-1.5">
                                <input name="kecamatan" value="{{ $kampung->kecamatan }}" class="w-full rounded border border-slate-300 text-xs px-2 py-1.5">
                                <label class="flex items-center gap-2 text-xs">
                                    <input type="checkbox" name="status_aktif" value="1" @checked($kampung->status_aktif)> Aktif
                                </label>
                                <button class="w-full rounded bg-slate-900 text-white text-xs font-medium px-3 py-1.5 hover:bg-slate-800">Simpan</button>
                            </form>
                        </div>
                    </details>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">{{ $kampungList->links() }}</div>
</div>
@endsection
