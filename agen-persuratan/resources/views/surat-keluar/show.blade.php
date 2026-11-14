@extends('layouts.app')
@section('judul', $surat->nomor_agenda)
@section('isi')
<h1>Surat Keluar {{ $surat->nomor_agenda }}</h1>
<div class="card"><dl class="dl">
<dt>Nomor surat</dt><dd>{{ $surat->nomor_surat }}</dd><dt>Tanggal surat</dt><dd>{{ $surat->tanggal_surat->translatedFormat('d F Y') }}</dd>
<dt>Tujuan</dt><dd>{{ $surat->tujuan }}</dd><dt>Perihal</dt><dd>{{ $surat->perihal }}</dd>
<dt>Sifat</dt><dd>@include('partials.tag', ['nilai' => $surat->sifat])</dd><dt>Klasifikasi</dt><dd>{{ $surat->klasifikasi ?: '-' }}</dd>
<dt>Ringkasan</dt><dd>{{ $surat->ringkasan ?: '-' }}</dd><dt>Dicatat oleh</dt><dd>{{ $surat->pembuat->name }}</dd></dl></div>
<div class="card"><h2>Berkas arsip</h2>@include('partials.dokumen', ['dokumen' => $surat->dokumen])</div>
@endsection
