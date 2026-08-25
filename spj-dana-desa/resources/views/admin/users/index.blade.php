@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Tambah Akun</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
        @csrf
        <input name="name" placeholder="Nama" required class="rounded border border-slate-300 text-sm px-3 py-2">
        <input name="email" type="email" placeholder="Email" required class="rounded border border-slate-300 text-sm px-3 py-2">
        <select name="role" required class="rounded border border-slate-300 text-sm px-3 py-2">
            <option value="">Pilih role</option>
            @foreach (['kaur_keuangan', 'kepala_kampung', 'pendamping', 'inspektorat', 'admin'] as $role)
            <option value="{{ $role }}">{{ $role }}</option>
            @endforeach
        </select>
        <select name="kampung_id" class="rounded border border-slate-300 text-sm px-3 py-2">
            <option value="">— (lintas kampung) —</option>
            @foreach ($kampungList as $kampung)
            <option value="{{ $kampung->id }}">{{ $kampung->nama_kampung }}</option>
            @endforeach
        </select>
        <button class="rounded bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-800">Simpan</button>
    </form>
    <p class="text-xs text-slate-400 mt-2">Kata sandi sementara akan ditampilkan setelah akun dibuat — sampaikan ke pengguna secara aman.</p>
</div>

<div class="bg-white rounded-lg border border-slate-200 p-4">
    <h2 class="text-sm font-semibold mb-3">Daftar Pengguna ({{ $userList->total() }})</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b border-slate-100">
                <th class="py-1.5">Nama</th>
                <th class="py-1.5">Email</th>
                <th class="py-1.5">Role</th>
                <th class="py-1.5">Kampung</th>
                <th class="py-1.5">Status</th>
                <th class="py-1.5"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($userList as $user)
            <tr class="border-b border-slate-50">
                <td class="py-2">{{ $user->name }}</td>
                <td class="py-2">{{ $user->email }}</td>
                <td class="py-2">{{ $user->role }}</td>
                <td class="py-2">{{ $user->kampung?->nama_kampung ?? '-' }}</td>
                <td class="py-2">
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td class="py-2 space-x-2">
                    <details class="relative inline-block">
                        <summary class="list-none cursor-pointer text-sky-700 hover:underline text-xs">Ubah</summary>
                        <div class="absolute right-0 mt-2 w-72 bg-white border border-slate-200 rounded-lg shadow-lg p-3 z-10">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-2">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $user->name }}" class="w-full rounded border border-slate-300 text-xs px-2 py-1.5">
                                <select name="role" class="w-full rounded border border-slate-300 text-xs px-2 py-1.5">
                                    @foreach (['kaur_keuangan', 'kepala_kampung', 'pendamping', 'inspektorat', 'admin'] as $role)
                                    <option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>
                                    @endforeach
                                </select>
                                <select name="kampung_id" class="w-full rounded border border-slate-300 text-xs px-2 py-1.5">
                                    <option value="">— (lintas kampung) —</option>
                                    @foreach ($kampungList as $kampung)
                                    <option value="{{ $kampung->id }}" @selected($user->kampung_id === $kampung->id)>{{ $kampung->nama_kampung }}</option>
                                    @endforeach
                                </select>
                                <label class="flex items-center gap-2 text-xs">
                                    <input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Aktif
                                </label>
                                <button class="w-full rounded bg-slate-900 text-white text-xs font-medium px-3 py-1.5 hover:bg-slate-800">Simpan</button>
                            </form>
                        </div>
                    </details>

                    @if ($user->role === 'pendamping')
                    <details class="relative inline-block">
                        <summary class="list-none cursor-pointer text-sky-700 hover:underline text-xs">Wilayah Binaan</summary>
                        <div class="absolute right-0 mt-2 w-64 bg-white border border-slate-200 rounded-lg shadow-lg p-3 z-10">
                            <form method="POST" action="{{ route('admin.users.wilayah-binaan', $user) }}" class="space-y-2">
                                @csrf @method('PUT')
                                <div class="max-h-40 overflow-y-auto space-y-1">
                                    @foreach ($kampungList as $kampung)
                                    <label class="flex items-center gap-2 text-xs">
                                        <input type="checkbox" name="kampung_ids[]" value="{{ $kampung->id }}"
                                            @checked($user->kampungBinaan->contains('id', $kampung->id))>
                                        {{ $kampung->nama_kampung }}
                                    </label>
                                    @endforeach
                                </div>
                                <button class="w-full rounded bg-slate-900 text-white text-xs font-medium px-3 py-1.5 hover:bg-slate-800">Simpan</button>
                            </form>
                        </div>
                    </details>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">{{ $userList->links() }}</div>
</div>
@endsection
