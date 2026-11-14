@extends('layouts.app')
@section('judul', 'Pencarian')
@section('isi')
<h1>Pencarian Surat</h1>
<form class="card filter" method="GET"><input name="q" value="{{ $kata }}" placeholder="Ketik kata kunci: nomor, asal/tujuan, perihal, isi surat…" autofocus><button class="btn">Cari</button></form>
@if($kata !== '')
<div class="card"><h2>Surat masuk ({{ $masuk->count() }})</h2><div class="scroll"><table class="tbl">
@forelse($masuk as $s)<tr><td><a href="{{ route('surat-masuk.show', $s) }}">{{ $s->nomor_agenda }}</a></td><td>{{ $s->tanggal_terima->format('d/m/Y') }}</td><td>{{ $s->asal_surat }}</td><td>{{ \Illuminate\Support\Str::limit($s->perihal, 100) }}</td></tr>
@empty<tr><td class="mut">Tidak ditemukan.</td></tr>@endforelse</table></div></div>
@if(auth()->user()->bolehLihatSemua())
<div class="card"><h2>Surat keluar ({{ $keluar->count() }})</h2><div class="scroll"><table class="tbl">
@forelse($keluar as $s)<tr><td><a href="{{ route('surat-keluar.show', $s) }}">{{ $s->nomor_agenda }}</a></td><td>{{ $s->tanggal_surat->format('d/m/Y') }}</td><td>{{ $s->tujuan }}</td><td>{{ \Illuminate\Support\Str::limit($s->perihal, 100) }}</td></tr>
@empty<tr><td class="mut">Tidak ditemukan.</td></tr>@endforelse</table></div></div>
@endif @endif
@endsection
