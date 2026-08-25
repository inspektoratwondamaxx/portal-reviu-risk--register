<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — SPJ Dana Desa Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    @auth
    <div class="flex min-h-screen">
        <aside class="w-64 shrink-0 bg-slate-900 text-slate-200 flex flex-col">
            <div class="px-5 py-5 border-b border-slate-800">
                <p class="font-semibold text-white leading-tight">SPJ Dana Desa</p>
                <p class="text-xs text-slate-400">Kabupaten Teluk Wondama</p>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                <a href="{{ route('dashboard') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : '' }}">Ringkasan</a>
                <a href="{{ route('spj.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('spj.*') ? 'bg-slate-800 text-white' : '' }}">SPJ &amp; Persetujuan</a>
                @if (auth()->user()->role === 'admin')
                <p class="px-3 pt-4 pb-1 text-xs uppercase tracking-wide text-slate-500">Master Data</p>
                <a href="{{ route('admin.kampung.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.kampung.*') ? 'bg-slate-800 text-white' : '' }}">Kampung</a>
                <a href="{{ route('admin.kegiatan.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.kegiatan.*') ? 'bg-slate-800 text-white' : '' }}">Kegiatan &amp; Pagu</a>
                <a href="{{ route('admin.kode-rekening.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.kode-rekening.*') ? 'bg-slate-800 text-white' : '' }}">Kode Rekening</a>
                <a href="{{ route('admin.users.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white' : '' }}">Pengguna</a>
                @endif
            </nav>
            <div class="px-4 py-4 border-t border-slate-800 text-xs">
                <p class="text-white">{{ auth()->user()->name }}</p>
                <p class="text-slate-400">{{ auth()->user()->role }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="text-slate-400 hover:text-white underline">Keluar</button>
                </form>
            </div>
        </aside>

        <main class="flex-1 min-w-0">
            <header class="bg-white border-b border-slate-200 px-6 py-4">
                <h1 class="text-lg font-semibold">@yield('title', 'Dashboard')</h1>
            </header>

            <div class="p-6 space-y-6">
                @if (session('status'))
                <div class="rounded border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
                @endif

                @if ($errors->any())
                <div class="rounded border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    @else
        @yield('content')
    @endauth
</body>
</html>
