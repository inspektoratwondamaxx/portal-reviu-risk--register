@extends('layouts.app')

@section('title', 'Verifikasi 2FA')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <h1 class="text-xl font-semibold">Verifikasi Dua Faktor</h1>
            <p class="text-sm text-slate-500">Wajib untuk role Inspektorat &amp; Admin</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-4">
            @if ($errors->any())
            <div class="rounded border border-rose-200 bg-rose-50 text-rose-800 px-3 py-2 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            @if ($otpauthUrl)
            <div class="rounded border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-900 space-y-2">
                <p class="font-medium">Pengaturan pertama kali</p>
                <p>Tambahkan akun ini di aplikasi authenticator (Google Authenticator/Authy) dengan menyalin kode berikut secara manual, lalu masukkan 6 digit kode yang muncul di bawah ini.</p>
                <code class="block break-all bg-white rounded border border-amber-200 px-2 py-1 text-xs">{{ $otpauthUrl }}</code>
            </div>
            @endif

            <form method="POST" action="{{ route('login.2fa') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1" for="otp">Kode OTP (6 digit)</label>
                    <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm tracking-widest text-center focus:border-slate-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full rounded bg-slate-900 text-white text-sm font-medium py-2 hover:bg-slate-800">
                    Verifikasi
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
