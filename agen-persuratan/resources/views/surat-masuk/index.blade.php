@extends('layouts.app')
@section('judul', 'Surat Masuk')
@section('isi')
<h1>Register Surat Masuk</h1>
<div class="card">
<form class="filter" method="GET">
  <input name="q" value="{{ request('q') }}" placeholder="Cari nomor, asal, perihal, isi surat…">
  <select name="status"><option value="">Semua status</option>@foreach(['baru','didisposisi','selesai'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
  <select name="sifat"><option value="">Semua sifat</option>@foreach(\App\Models\SuratMasuk::SIFAT as $s)<option value="{{ $s }}" @selected(request('sifat')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
  <input name="tahun" type="number" value="{{ request('tahun') }}" placeholder="Tahun">
  <button class="btn">Saring</button>
</form>
@if(auth()->user()->punyaPeran('admin','operator'))<p><a class="btn" href="{{ route('surat-masuk.create') }}">+ Catat surat masuk</a></p>@endif
<div class="scroll"><table class="tbl"><tr><th>No. Agenda</th><th>Tgl Terima</th><th>No. Surat</th><th>Asal</th><th>Perihal</th><th>Sifat</th><th>Status</th><th>Berkas</th></tr>
@forelse($surat as $s)
<tr><td><a href="{{ route('surat-masuk.show', $s) }}">{{ $s->nomor_agenda }}</a></td><td>{{ $s->tanggal_terima->format('d/m/Y') }}</td>
<td>{{ $s->nomor_surat }}</td><td>{{ $s->asal_surat }}</td><td>{{ \Illuminate\Support\Str::limit($s->perihal, 80) }}</td>
<td>@include('partials.tag', ['nilai' => $s->sifat])</td><td>@include('partials.tag', ['nilai' => $s->status])</td><td>{{ $s->dokumen_count }}</td></tr>
@empty<tr><td colspan="8" class="mut">Tidak ada data.</td></tr>@endforelse</table></div>
{{ $surat->links() }}</div>
@endsection
