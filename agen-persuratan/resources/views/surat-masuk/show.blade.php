@extends('layouts.app')
@section('judul', $surat->nomor_agenda)
@section('isi')
<h1>Surat Masuk {{ $surat->nomor_agenda }}</h1>
<div class="card"><dl class="dl">
<dt>Nomor surat</dt><dd>{{ $surat->nomor_surat }}</dd><dt>Tanggal surat</dt><dd>{{ $surat->tanggal_surat->translatedFormat('d F Y') }}</dd>
<dt>Tanggal diterima</dt><dd>{{ $surat->tanggal_terima->translatedFormat('d F Y') }}</dd><dt>Asal surat</dt><dd>{{ $surat->asal_surat }}</dd>
<dt>Perihal</dt><dd>{{ $surat->perihal }}</dd><dt>Sifat</dt><dd>@include('partials.tag', ['nilai' => $surat->sifat])</dd>
<dt>Status</dt><dd>@include('partials.tag', ['nilai' => $surat->status])</dd><dt>Klasifikasi</dt><dd>{{ $surat->klasifikasi ?: '-' }}</dd>
<dt>Ringkasan</dt><dd>{{ $surat->ringkasan ?: '-' }}</dd><dt>Dicatat oleh</dt><dd>{{ $surat->pembuat->name }}</dd></dl></div>
<div class="card"><h2>Berkas arsip</h2>@include('partials.dokumen', ['dokumen' => $surat->dokumen])</div>

<div class="card"><h2>Disposisi</h2>
@forelse($surat->disposisi as $d)
<div style="border-top:1px solid var(--br);padding:10px 0">
  <strong>{{ $d->dari->name }} → {{ $d->kepada->name }}</strong> @include('partials.tag', ['nilai' => $d->status])
  <div>{{ implode('; ', $d->instruksi) }}</div>
  @if($d->catatan)<div class="mut">Catatan: {{ $d->catatan }}</div>@endif
  <div class="mut">Batas waktu: {{ $d->batas_waktu?->translatedFormat('d F Y') ?? '-' }}@if($d->tanggapan) · Tanggapan: {{ $d->tanggapan }}@endif</div>
  @if(($d->kepada_user_id === auth()->id() || auth()->user()->punyaPeran('admin','pimpinan')) && $d->status !== 'selesai')
  <form method="POST" action="{{ route('disposisi.update', $d) }}" class="filter" style="margin-top:8px">@csrf @method('PATCH')
    <select name="status"><option value="diproses">Sedang diproses</option><option value="selesai">Selesai</option></select>
    <input name="tanggapan" placeholder="Tanggapan/hasil tindak lanjut"><button class="btn kecil">Perbarui</button></form>
  @endif
</div>
@empty<p class="mut">Belum ada disposisi.</p>@endforelse
</div>

@if(auth()->user()->punyaPeran('admin','pimpinan'))
<form class="card" method="POST" action="{{ route('disposisi.store', $surat) }}">@csrf
  <h2>Buat disposisi</h2>
  <div class="row">
    <div><label>Diteruskan kepada</label><select name="kepada_user_id" required><option value="">— pilih —</option>
      @foreach($penerima as $u)<option value="{{ $u->id }}" @selected(old('kepada_user_id')==$u->id)>{{ $u->name }}{{ $u->jabatan ? ' – '.$u->jabatan : '' }}</option>@endforeach</select>
      @error('kepada_user_id')<div class="err-t">{{ $message }}</div>@enderror</div>
    <div><label>Batas waktu</label><input type="date" name="batas_waktu" value="{{ old('batas_waktu') }}">@error('batas_waktu')<div class="err-t">{{ $message }}</div>@enderror</div>
    <div class="full"><label>Instruksi</label><div class="chk">@foreach($daftarInstruksi as $i)
      <label><input type="checkbox" name="instruksi[]" value="{{ $i }}" @checked(in_array($i, old('instruksi', [])))> {{ $i }}</label>@endforeach</div>
      @error('instruksi')<div class="err-t">{{ $message }}</div>@enderror</div>
    <div class="full"><label>Catatan</label><textarea name="catatan">{{ old('catatan') }}</textarea></div>
  </div>
  <p><button class="btn">Kirim disposisi</button></p>
</form>
@endif
@endsection
