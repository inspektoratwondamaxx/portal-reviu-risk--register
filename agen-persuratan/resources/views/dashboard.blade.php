@extends('layouts.app')
@section('judul', 'Beranda')
@section('isi')
<h1>Beranda</h1>
<div class="grid" style="margin-bottom:16px">
  <div class="card stat"><b>{{ $masukBulanIni }}</b><span>Surat masuk bulan ini</span></div>
  @if(auth()->user()->bolehLihatSemua())
  <div class="card stat"><b>{{ $belumDisposisi }}</b><span>Belum didisposisi</span></div>
  <div class="card stat"><b>{{ $keluarBulanIni }}</b><span>Surat keluar bulan ini</span></div>
  @endif
  <div class="card stat"><b>{{ $disposisiSaya }}</b><span>Disposisi untuk saya</span></div>
</div>

@if($disposisiUntukSaya->isNotEmpty())
<div class="card"><h2>Disposisi yang perlu ditindaklanjuti</h2><div class="scroll"><table class="tbl">
<tr><th>Surat</th><th>Dari</th><th>Batas waktu</th><th>Status</th></tr>
@foreach($disposisiUntukSaya as $d)
<tr><td><a href="{{ route('surat-masuk.show', $d->suratMasuk) }}">{{ $d->suratMasuk->nomor_agenda }}</a><br>{{ \Illuminate\Support\Str::limit($d->suratMasuk->perihal, 80) }}</td>
<td>{{ $d->dari->name }}</td><td>{{ $d->batas_waktu?->translatedFormat('d M Y') ?? '-' }}</td>
<td>@include('partials.tag', ['nilai' => $d->status])</td></tr>
@endforeach</table></div></div>
@endif

<div class="card"><h2>Surat masuk terbaru</h2>
@if(auth()->user()->punyaPeran('admin','operator'))<p><a class="btn" href="{{ route('surat-masuk.create') }}">+ Catat surat masuk</a></p>@endif
<div class="scroll"><table class="tbl"><tr><th>Agenda</th><th>Asal</th><th>Perihal</th><th>Status</th></tr>
@forelse($terbaru as $s)
<tr><td><a href="{{ route('surat-masuk.show', $s) }}">{{ $s->nomor_agenda }}</a></td><td>{{ $s->asal_surat }}</td>
<td>{{ \Illuminate\Support\Str::limit($s->perihal, 90) }}</td><td>@include('partials.tag', ['nilai' => $s->status])</td></tr>
@empty<tr><td colspan="4" class="mut">Belum ada data.</td></tr>@endforelse</table></div></div>
@endsection
