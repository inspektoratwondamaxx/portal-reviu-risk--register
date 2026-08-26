@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-xl font-semibold">SPJ Dana Desa Digital</h1>
            <p class="text-sm text-slate-500">Kabupaten Teluk Wondama</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6">
            @if (session('status'))
            <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 text-emerald-800 px-3 py-2 text-sm">
                {{ session('status') }}
            </div>
            @endif

            @if ($errors->any())
            <div class="mb-4 rounded border border-rose-200 bg-rose-50 text-rose-800 px-3 py-2 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1" for="email">Email</label>
                    <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="password">Kata Sandi</label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300">
                    Ingat saya
                </label>
                <button type="submit" class="w-full rounded bg-slate-900 text-white text-sm font-medium py-2 hover:bg-slate-800">
                    Masuk
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            Role inspektorat &amp; admin wajib verifikasi dua faktor (2FA) setelah masuk.
        </p>
    </div>
</div>
@endsection
