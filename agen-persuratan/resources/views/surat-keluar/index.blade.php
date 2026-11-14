@extends('layouts.app')
@section('judul', 'Surat Keluar')
@section('isi')
<h1>Register Surat Keluar</h1>
<div class="card">
<form class="filter" method="GET"><input name="q" value="{{ request('q') }}" placeholder="Cari nomor, tujuan, perihal…"><input name="tahun" type="number" value="{{ request('tahun') }}" placeholder="Tahun"><button class="btn">Saring</button></form>
@if(auth()->user()->punyaPeran('admin','operator'))<p><a class="btn" href="{{ route('surat-keluar.create') }}">+ Catat surat keluar</a></p>@endif
<div class="scroll"><table class="tbl"><tr><th>No. Agenda</th><th>Tanggal</th><th>No. Surat</th><th>Tujuan</th><th>Perihal</th><th>Sifat</th><th>Berkas</th></tr>
@forelse($surat as $s)
<tr><td><a href="{{ route('surat-keluar.show', $s) }}">{{ $s->nomor_agenda }}</a></td><td>{{ $s->tanggal_surat->format('d/m/Y') }}</td><td>{{ $s->nomor_surat }}</td>
<td>{{ $s->tujuan }}</td><td>{{ \Illuminate\Support\Str::limit($s->perihal, 80) }}</td><td>@include('partials.tag', ['nilai' => $s->sifat])</td><td>{{ $s->dokumen_count }}</td></tr>
@empty<tr><td colspan="7" class="mut">Tidak ada data.</td></tr>@endforelse</table></div>{{ $surat->links() }}</div>
@endsection
